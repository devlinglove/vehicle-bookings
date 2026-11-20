<?php

use Workerman\Worker;
use Workerman\Connection\TcpConnection;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/db.php'; // your db(bool $reconnect = false): PDO

class SeatUnavailableException extends \RuntimeException {}

$ws = new Worker('websocket://0.0.0.0:2346');

// Keep this at 1: subscriptions live in this process's memory.
// With count > 1, a booking handled by one process won't reach clients on another.
$ws->count = 1;

$ws->onConnect = function (TcpConnection $connection) {
    $connection->page       = null;   // 'trips' or 'trip'
    $connection->tripId     = null;
    $connection->terminalId = null;   // trips page filter (null = all terminals)
};

$ws->onMessage = function (TcpConnection $connection, $data) use ($ws) {
    try {
        $message = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $e) {
        sendError($connection, 'invalid_json', 'Message must be valid JSON');
        return;
    }

    if (!is_array($message)) {
        sendError($connection, 'invalid_message', 'Message must be a JSON object');
        return;
    }

    try {
        switch ($message['action'] ?? '') {

            // ---------- Trips page ----------
            case 'subscribe_trips':
                $connection->page       = 'trips';
                $connection->tripId     = null;
                $connection->terminalId = positiveInt($message['terminal_id'] ?? null);
                $connection->send(encodeMessage('all_trips', getAllTrips($connection->terminalId)));
                break;

            // ---------- Seats page ----------
            case 'subscribe_trip':
                $tripId = positiveInt($message['trip_id'] ?? null);
                if (!$tripId) {
                    sendError($connection, 'invalid_trip', 'Invalid trip id');
                    break;
                }

                $trip = getTripById($tripId);
                if (!$trip) {
                    sendError($connection, 'trip_not_found', 'Trip not found');
                    break;
                }

                $connection->page   = 'trip';
                $connection->tripId = $tripId;
                $connection->send(encodeMessage('trip_detail', [
                    'trip'  => $trip,
                    'seats' => getTripSeats($tripId),
                ]));
                break;

            case 'book_seat':
                $tripId = positiveInt($message['trip_id'] ?? null);
                $seatId = positiveInt($message['seat_id'] ?? null);
                if (!$tripId || !$seatId) {
                    sendError($connection, 'invalid_request', 'trip_id and seat_id are required');
                    break;
                }

                bookSeat($tripId, $seatId); // throws SeatUnavailableException

                $connection->send(encodeMessage('booking_confirmed', [
                    'trip_id' => $tripId,
                    'seat_id' => $seatId,
                ]));

                // The booking already succeeded; a failure while notifying
                // others must not be reported to the booker as an error.
                try {
                    notifyTripChanged($ws, $tripId);
                } catch (\Throwable $e) {
                    error_log('[ws] notify failed for trip ' . $tripId . ': ' . $e->getMessage());
                }
                break;

            default:
                sendError($connection, 'unknown_action', 'Unknown action');
        }
    } catch (SeatUnavailableException $e) {
        sendError($connection, 'seat_unavailable', $e->getMessage());
    } catch (\PDOException $e) {
        error_log('[ws] DB error: ' . $e->getMessage());
        sendError($connection, 'db_error', 'Could not complete the request, please retry');
    } catch (\Throwable $e) {
        error_log('[ws] Unhandled: ' . $e);
        sendError($connection, 'server_error', 'Something went wrong');
    }
};

// =====================================================================
// Messaging helpers
// =====================================================================

function encodeMessage(string $type, $data): string
{
    return json_encode(['type' => $type, 'data' => $data], JSON_THROW_ON_ERROR);
}

function sendError(TcpConnection $conn, string $code, string $msg): void
{
    $conn->send(json_encode(['type' => 'error', 'code' => $code, 'message' => $msg]));
}

function sendToAll(array $connections, string $payload): void
{
    foreach ($connections as $conn) {
        try {
            $conn->send($payload);
        } catch (\Throwable $e) {
            error_log('[ws] send failed to ' . $conn->id . ': ' . $e->getMessage());
        }
    }
}

/** Push fresh data to everyone viewing this trip, and to everyone on the trips list. */
function notifyTripChanged(Worker $ws, int $tripId): void
{
    $tripViewers = [];
    $listViewers = []; // grouped by terminal: '' => all terminals, '3' => terminal 3

    foreach ($ws->connections as $conn) {
        if ($conn->page === 'trip' && $conn->tripId === $tripId) {
            $tripViewers[] = $conn;
        } elseif ($conn->page === 'trips') {
            $listViewers[(string) $conn->terminalId][] = $conn;
        }
    }

    // Query once per group, not once per connection
    if ($tripViewers) {
        sendToAll($tripViewers, encodeMessage('trip_detail', [
            'trip'  => getTripById($tripId),
            'seats' => getTripSeats($tripId),
        ]));
    }

    foreach ($listViewers as $terminalKey => $connections) {
        $terminalId = $terminalKey === '' ? null : (int) $terminalKey;
        sendToAll($connections, encodeMessage('all_trips', getAllTrips($terminalId)));
    }
}

function positiveInt($value): ?int
{
    $int = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $int === false ? null : $int;
}

// =====================================================================
// Database
// =====================================================================

/** Run $fn with a PDO; reconnect and retry once only if the connection was lost. */
function withDb(callable $fn)
{
    try {
        return $fn(db());
    } catch (\PDOException $e) {
        if (!isConnectionLost($e)) {
            throw $e;
        }
        return $fn(db(true));
    }
}

function isConnectionLost(\PDOException $e): bool
{
    $code = $e->errorInfo[1] ?? null;
    return in_array($code, [2006, 2013], true)
        || str_contains($e->getMessage(), 'gone away');
}

const TRIP_SELECT = "
    SELECT
        tr.trip_id,
        tr.departure_time,
        tr.fare,
        r.route_name,
        s.stand_name,
        s.status AS stand_status,
        v.vehicle_number,
        v.vehicle_type,
        v.vehicle_name,
        (SELECT COUNT(*) FROM trip_seats ts
            WHERE ts.trip_id = tr.trip_id) AS total_seats,
        (SELECT COUNT(*) FROM trip_seats ts
            WHERE ts.trip_id = tr.trip_id AND ts.status = 'available') AS available_seats
    FROM trips tr
    JOIN routes r   ON r.route_id = tr.route_id
    JOIN stands s   ON s.stand_id = tr.departure_stand_id
    JOIN vehicles v ON v.vehicle_id = tr.vehicle_id
";

function getAllTrips(?int $terminalId = null): array
{
    $sql    = TRIP_SELECT . " WHERE tr.status = 'scheduled' AND tr.departure_time >= NOW()";
    $params = [];

    if ($terminalId !== null) {
        // Use the same condition your controller uses to filter by terminal
        $sql     .= " AND s.terminal_id = ?";
        $params[] = $terminalId;
    }

    $sql .= " ORDER BY tr.departure_time ASC";

    return withDb(function (PDO $pdo) use ($sql, $params) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    });
}

function getTripById(int $tripId): ?array
{
    $sql = TRIP_SELECT . " WHERE tr.trip_id = ?";

    return withDb(function (PDO $pdo) use ($sql, $tripId) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tripId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    });
}

function getTripSeats(int $tripId): array
{
    // Adjust column names to your trip_seats table
    $sql = "
        SELECT seat_id, seat_number, status
        FROM trip_seats
        WHERE trip_id = ?
        ORDER BY seat_number
    ";

    return withDb(function (PDO $pdo) use ($sql, $tripId) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tripId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    });
}

function bookSeat(int $tripId, int $seatId): void
{
    withDb(function (PDO $pdo) use ($tripId, $seatId) {
        $pdo->beginTransaction();

        try {
            // FOR UPDATE locks the row so two clients can't book the same seat
            $stmt = $pdo->prepare("
                SELECT ts.status
                FROM trip_seats ts
                JOIN trips tr ON tr.trip_id = ts.trip_id
                WHERE ts.trip_id = ?
                  AND ts.seat_id = ?
                  AND tr.status = 'scheduled'
                  AND tr.departure_time >= NOW()
                FOR UPDATE
            ");
            $stmt->execute([$tripId, $seatId]);
            $status = $stmt->fetchColumn();

            if ($status === false) {
                throw new SeatUnavailableException('Seat not found or trip no longer bookable');
            }
            if ($status !== 'available') {
                throw new SeatUnavailableException('This seat has already been taken');
            }

            // TODO: also store who booked it (user id from your session/auth)
            $pdo->prepare("
                UPDATE trip_seats SET status = 'booked'
                WHERE trip_id = ? AND seat_id = ?
            ")->execute([$tripId, $seatId]);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                try {
                    $pdo->rollBack();
                } catch (\Throwable) {
                    // connection may already be dead; nothing more to do
                }
            }
            throw $e;
        }
    });
}

Worker::runAll();