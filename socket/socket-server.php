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
            'mysql:host=127.0.0.1;dbname=vehicle_bookings;charset=utf8mb4',
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

function getSeatsByTripId(int $id): array
{

    $sql = "SELECT 
                    ts.id, trip_id, 
                    ts.seat_id, price, 
                    status, 
                    locked_until, 
                    s.seat_number, 
                    s.seat_class
                FROM trip_seats ts INNER JOIN seats s ON s.id = ts.seat_id  
                WHERE trip_id = ?";
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

    var_dump($tripId, $seatId, $status);

    if($status == 'available'){

        $sql = "
            UPDATE trip_seats
            SET status = :status,
            locked_until = NULL
            WHERE seat_id = :seat_id 
            AND trip_id = :trip_id
        ";
    }

    if ($status == 'hold') {

        $sql = "
            UPDATE trip_seats
            SET status = :status,
            locked_until = DATE_ADD(NOW(), INTERVAL 1 MINUTE)
            WHERE seat_id = :seat_id 
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
        $stmt = db(true)->prepare($sql);
        $stmt->execute([
            ':seat_id' => $seatId,
            ':trip_id' => $tripId,
        ]);
    }

    return $stmt->rowCount() === 1;
}


function updateLockedSeats()
{
    try {
        $selectSql = "
        SELECT
            id,
            trip_id,
            seat_id
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
    $payload = json_encode(getSeatsByTripId($tripId));
    foreach ($ws->connections as $conn) {
        if (($conn->tripId ?? null) === $tripId) {
            $conn->send($payload);
        }
    }
}

$ws = new Worker('websocket://0.0.0.0:8080');
$ws->count = 1;

$ws->onWorkerStart = function () use ($ws) {
    db();
    echo "Database connected\n";

    $time_interval = 60;
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

$ws->onMessage = function (TcpConnection $connection, $data) use ($ws) {

    $message = json_decode($data, true);

    if (($message['action'] ?? '') === 'join_trip') {

        $connection->tripId = (int) $message['trip_id'];

        echo "Connection {$connection->id} joined trip {$connection->tripId}\n";

        try {
            $seats = getSeatsByTripId((int) $message['trip_id']);
            $connection->send(json_encode($seats)); // encode once only
        } catch (PDOException $e) {

            echo "DB error: {$e->getMessage()}\n";
            $connection->send(json_encode(['error' => 'Could not load trips']));
        }
    }

    if (($message['action'] ?? '') === 'seat_status_change') {

        $connection->tripId = (int) $message['trip_id'];
        $connection->seatId = (int) $message['seat_id'];

        try {
            $isSuccess = updateSeatStatusByTripId((int) $message['trip_id'], (int) $message['seat_id'], $message['status']);
            //$seatsAfterUpdate = getSeatsByTripId((int) $message['trip_id']);
            //$connection->send(json_encode($seatsAfterUpdate)); 

            broadcastSeats($ws, (int) $message['trip_id']);

        
        } catch (PDOException $e) {

            echo "DB error: {$e->getMessage()}\n";
            $connection->send(json_encode(['error' => 'Could not load trips']));
        }
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
