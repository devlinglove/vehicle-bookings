<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Framework\TripDb;
use Workerman\Connection\TcpConnection;
use Workerman\Timer;
use Workerman\Worker;

function db(bool $reconnect = false): PDO
{
    static $pdo = null;

    if ($pdo === null || $reconnect) {
        $pdo = new PDO(
            'mysql:host=127.0.0.1;dbname=vehicle_bookings_management;charset=utf8mb4',
            'sample',
            'Zxcvb123@',
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    return $pdo;
}

function getTrips(): array
{
    $sql = "SELECT 
        
        v.registration_number,  
        t.origin,  
        t.destination,  
        t.departure_time, 
        t.arrival_time 

    FROM trips t INNER JOIN vehicles v ON t.vehicle_id = v.id";

    try {
        return db()->query($sql)->fetchAll();
    } catch (PDOException $e) {
        return db(true)->query($sql)->fetchAll();
    }
}

function isConnectionLost(\PDOException $e): bool
{
    $code = $e->errorInfo[1] ?? null;
    return in_array($code, [2006, 2013], true) || str_contains($e->getMessage(), 'gone away');
}


function getAllTrips(): array{
    $sql = "
        SELECT
            s.stand_name,
            s.status,
            r.route_name,
            v.vehicle_number,
            v.vehicle_type,
            v.vehicle_name, 
            tr.trip_id, 
            tr.departure_time, 
            s.`status` AS stand_status,
            tr.fare,
            (SELECT COUNT(*) FROM trip_seats ts
                WHERE ts.trip_id = tr.trip_id) AS total_seats,

            (SELECT COUNT(*) FROM trip_seats ts
                WHERE ts.trip_id = tr.trip_id AND ts.status = 'available') AS available_seats
            
        FROM trips tr
        JOIN routes r ON tr.route_id = r.route_id
        JOIN stands s ON s.stand_id = tr.departure_stand_id
        JOIN vehicles v ON v.vehicle_id = tr.vehicle_id
        WHERE tr.status = 'scheduled'
        ORDER BY tr.departure_time DESC
    ";

    try {
        return db()->query($sql)->fetchAll();
    } catch (PDOException $e) {
        if (!isConnectionLost($e)) {
            throw $e;
        }
        return db(true)->query($sql)->fetchAll();
    }
}

function getListRecords($query, $id): array
{
    //$config = require basePath('config/db2.php');
    $config = [
        'host' => 'localhost',
        'port' => 3306,
        'dbname' => 'vehicle_bookings',
        'username' => 'sample',
        'password' => 'Zxcvb123@'
    ];
    $db = new TripDb($config);

    try {
        $stmt = $db()->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        throw new Exception("query failed: {$e->getMessage()}");
    }
}


function getTripByTripId(int $id): array{
    $sql = "
        SELECT
            s.stand_name,
            s.status,
            r.route_name,
            v.vehicle_number,
            v.vehicle_type,
            v.vehicle_name, 
            tr.trip_id, 
            tr.departure_time, 
            s.`status` AS stand_status,
            tr.fare,
            (SELECT COUNT(*) FROM trip_seats ts
                WHERE ts.trip_id = tr.trip_id) AS total_seats,

            (SELECT COUNT(*) FROM trip_seats ts
                WHERE ts.trip_id = tr.trip_id AND ts.status = 'available') AS available_seats
            
        FROM trips tr
        JOIN routes r ON tr.route_id = r.route_id
        JOIN stands s ON s.stand_id = tr.departure_stand_id
        JOIN vehicles v ON v.vehicle_id = tr.vehicle_id
        WHERE tr.status = 'scheduled'
        ORDER BY tr.departure_time DESC
    ";

    try {
        return db()->query($sql)->fetchAll();
    } catch (PDOException $e) {
        if (!isConnectionLost($e)) {
            throw $e;
        }
        return db(true)->query($sql)->fetchAll();
    }
}


function getSeatsByTripId(int $id): array
{
    $sql = 
        "SELECT
            ts.trip_seat_id,
            tr.trip_id,
            tr.vehicle_id, 
            tr.departure_time,
            ts.fare,
            ts.status,
            ts.seat_number
        FROM trip_seats ts JOIN trips tr ON tr.trip_id = ts.trip_id   
        WHERE tr.trip_id = ?";

    try {

        $stmt = db(true)->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetchAll();

    } catch (PDOException $e) {
        $stmt = db(true)->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }
}


function updateSeatStatusByTripId(int $tripId, int $seatId, string $status): bool
{

    $allowed = ['available', 'reserved', 'booked', 'hold'];

    if (!in_array($status, $allowed, true)) {
        throw new InvalidArgumentException("Invalid status: $status");
    }

    if($status == 'available'){

        $sql = "
            UPDATE trip_seats
            SET status = :status,
            locked_until = NULL
            WHERE trip_seat_id = :seat_id 
            AND trip_id = :trip_id
        ";
    }

    if ($status == 'hold') {

        $sql = "
            UPDATE trip_seats
            SET status = :status,
            locked_until = DATE_ADD(NOW(), INTERVAL 1 MINUTE)
            WHERE trip_seat_id = :seat_id 
            AND trip_id = :trip_id
            AND status = 'available'
        ";
    }

   

    try {
        $stmt = db(true)->prepare($sql);

        $stmt->execute([
            ':seat_id' => $seatId,
            ':trip_id' => $tripId,
            ':status'  => $status,
        ]);

    } catch (PDOException $e) {
        error_log('error occured while updateing seat status' . $e->getMessage());
        $stmt = db(true)->prepare($sql);
        $stmt->execute([
            ':seat_id' => $seatId,
            ':trip_id' => $tripId,
            ':status'  => $status,
        ]);
        
    }

    return $stmt->rowCount() === 1;
}


function updateLockedSeats()
{
    try {
        $selectSql = "
        SELECT
            trip_seat_id,
            trip_id
        FROM trip_seats
        WHERE status = 'hold'
          AND locked_until IS NOT NULL
          AND locked_until <= NOW()
    ";

        $selectStmt = db()->prepare($selectSql);
        $selectStmt->execute();
        $expiredSeats = $selectStmt->fetchAll();


        if (empty($expiredSeats)) {
            echo date('Y-m-d H:i:s') . " - No expired seats.\n";
            return [];
        }

        $updateSql = "
        UPDATE trip_seats
        SET
            status = 'available',
            locked_until = NULL
        WHERE status = 'hold'
          AND locked_until IS NOT NULL
          AND locked_until <= NOW()";

        $updateStmt = db()->prepare($updateSql);
        $updateStmt->execute();

        $releasedCount = $updateStmt->rowCount();

        echo date('Y-m-d H:i:s') . " - Released {$releasedCount} seats.\n";

        return $expiredSeats;

    } catch (Throwable $e) {
        echo date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function broadcastSeats(Worker $ws, int $tripId): void
{
    //$payload = json_encode(getSeatsByTripId($tripId));

    $payload = json_encode(
         ['type' => 'seat_status_change', 'data' => getSeatsByTripId($tripId)],
        JSON_THROW_ON_ERROR
    );

    foreach ($ws->connections as $conn) {
        if (($conn->tripId ?? null) === $tripId) {
            $conn->send($payload);
        }
    }
}

function broadcastAllTrips(Worker $ws): void
{
    $payload = json_encode(
        ['type' => 'all_trips', 'data' => getAllTrips()],
        JSON_THROW_ON_ERROR
    );

    foreach ($ws->connections as $conn) {
        try {
            $conn->send($payload);
        } catch (\Throwable $e) {
            error_log('[ws] send failed to ' . $conn->id . ': ' . $e->getMessage());
        }
    }
}

function sendError(TcpConnection $conn, string $code, string $msg): void
{
    $conn->send(json_encode(['type' => 'error', 'code' => $code, 'message' => $msg]));
}



$ws = new Worker('websocket://0.0.0.0:8080');
$ws->count = 1;

$ws->onWorkerStart = function () use ($ws) {
    db();
    echo "Database connected\n";

    $time_interval = 120;
    Timer::add($time_interval, function () use ($ws) {
        echo "task run\n";

        $released = updateLockedSeats();

        if (count($released) > 0) {
            foreach ($released as $item) {
                broadcastSeats($ws, (int) $item['trip_id']);
            }
        }

    });
};

$ws->onConnect = function ($connection) {
    echo "New connection: {$connection->id}\n";
};

// $ws->onMessage = function (TcpConnection $connection, $data) use ($ws) {

//     $message = json_decode($data, true);

//     try {
//         switch ($message['action'] ?? '') {
//             case 'all_trips':
//                 broadcastAllTrips($ws);
//                 break;
//             default:
//                 sendError($connection, 'unknown_action', 'Unknown action');
//         }
//     } catch (\PDOException $e) {
//         error_log('[ws] DB error: ' . $e->getMessage());
//         sendError($connection, 'db_error', 'Could not load trips, please retry');
//     } catch (\Throwable $e) {
//         error_log('[ws] Unhandled: ' . $e);   // full stack trace
//         sendError($connection, 'server_error', 'Something went wrong');
//     }

//     if (($message['action'] ?? '') === 'join_trip') {

//         $connection->tripId = (int) $message['trip_id'];

//         echo "Connection {$connection->id} joined trip {$connection->tripId}\n";

//         try {
//             $seats = getSeatsByTripId((int) $message['trip_id']);
//             $connection->send(json_encode($seats)); // encode once only
//         } catch (PDOException $e) {

//             echo "DB error: {$e->getMessage()}\n";
//             $connection->send(json_encode(['error' => 'Could not load trips']));
//         }
//     }

//     if (($message['action'] ?? '') === 'seat_status_change') {

//         $connection->tripId = (int) $message['trip_id'];
//         $connection->seatId = (int) $message['seat_id'];

//         try {
//             $isSuccess = updateSeatStatusByTripId((int) $message['trip_id'], (int) $message['seat_id'], $message['status']);
//             //$seatsAfterUpdate = getSeatsByTripId((int) $message['trip_id']);
//             //$connection->send(json_encode($seatsAfterUpdate)); 

//             broadcastSeats($ws, (int) $message['trip_id']);

        
//         } catch (PDOException $e) {

//             echo "DB error: {$e->getMessage()}\n";
//             $connection->send(json_encode(['error' => 'Could not load trips']));
//         }
//     }
// };

$ws->onMessage = function (TcpConnection $connection, $data) use ($ws) {

    $message = json_decode($data, true);

    if (!is_array($message)) {
        sendError($connection, 'invalid_json', 'Invalid JSON message');
        return;
    }

    try {
        switch ($message['action'] ?? '') {

            case 'all_trips':
                broadcastAllTrips($ws);
                break;

            case 'join_trip':

                $tripId = (int) ($message['trip_id'] ?? 0);

                if ($tripId <= 0) {
                    sendError($connection, 'invalid_trip', 'Invalid trip ID');
                    break;
                }

                $connection->tripId = $tripId;

                $seats = getSeatsByTripId($tripId);

                $payload = json_encode(
                    ['type' => 'join_trip', 'data' => $seats],
                    JSON_THROW_ON_ERROR
                );

                $connection->send($payload);

                break;

            case 'seat_status_change':

                $tripId = (int) ($message['trip_id'] ?? 0);
                $seatId = (int) ($message['seat_id'] ?? 0);
                $status = $message['status'] ?? '';

                if ($tripId <= 0 || $seatId <= 0) {
                    sendError($connection, 'invalid_data', 'Invalid trip or seat ID');
                    break;
                }

                $isSuccess = updateSeatStatusByTripId(
                    $tripId,
                    $seatId,
                    $status
                );

                if ($isSuccess) {
                    broadcastSeats($ws, $tripId);
                }

                break;

            default:
                sendError($connection, 'unknown_action', 'Unknown action');
        }

    } catch (PDOException $e) {

        error_log('[ws] DB error: ' . $e->getMessage());

        sendError(
            $connection,
            'db_error',
            'Could not process database request' . $e->getMessage()
        );

    } catch (Throwable $e) {

        error_log('[ws] Unhandled: ' . $e);

        sendError(
            $connection,
            'server_error',
            'Something went wrong'
        );
    }
};



$ws->onClose = function (TcpConnection $connection) use (&$tripConnections) {

    $tripId = $connection->tripId ?? null;

    if ($tripId === null) {
        return;
    }

    unset(
        $tripConnections[$tripId][$connection->id]
    );

    echo "Client {$connection->id} disconnected\n";
};


$ws->onError = function ($connection, $code, $msg) {
    echo "Error: $msg\n";
};

Worker::runAll();
