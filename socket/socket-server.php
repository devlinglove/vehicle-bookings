<?php 

require_once __DIR__ . '/../vendor/autoload.php';

use Framework\TripDb;
use Workerman\Connection\TcpConnection;
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


    $allowed = ['available', 'reserved', 'booked']; 

    if (!in_array($status, $allowed, true)) {
        throw new InvalidArgumentException("Invalid status: $status");
    }
    
    $sql = "
        UPDATE trip_seats
        SET status = :status,
        locked_until = DATE_ADD(NOW(), INTERVAL 5 MINUTE)
        WHERE seat_id = :seat_id 
        AND trip_id = :trip_id
        AND status = 'available'
    ";

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




$ws = new Worker('websocket://0.0.0.0:8080');
$ws->count = 2; // Windows supports only 1 process
$http = new Worker('http://127.0.0.1:8081');


$ws->onWorkerStart = function () {
    db(); // connect when the server starts, so config errors show up immediately
    echo "Database connected\n";
};

$ws->onConnect = function ($connection) {
    echo "New connection: {$connection->id}\n";
    // $connection->onWebSocketConnect = function ($connection) {
    //     try {
    //         $users = getTrips();
    //         $connection->send(json_encode($users)); // encode once only
    //     } catch (PDOException $e) {
    //         echo "DB error: {$e->getMessage()}\n";
    //         $connection->send(json_encode(['error' => 'Could not load trips']));
    //     }
    // };
};



// $ws->onMessage = function ($connection, $data) use ($ws) {
//     foreach ($ws->connections as $client) {
//         if ($client !== $connection) {
//             $client->send($data);
//         }
//     }
// };

$ws->onMessage = function (TcpConnection $connection, $data) {

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
            $seatsAfterUpdate = getSeatsByTripId((int) $message['trip_id']);
            $connection->send(json_encode($seatsAfterUpdate)); // encode once only
        } catch (PDOException $e) {
            
            echo "DB error: {$e->getMessage()}\n";
            $connection->send(json_encode(['error' => 'Could not load trips']));
        }
    }
};



// $ws->onClose = function ($connection) {
//     echo "Connection {$connection->id} closed\n";
// };

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

$http->onMessage = function (TcpConnection $connection, $request) use (&$tripConnections) {

    // $token = $request->header('X-Internal-Token');

    // if ($token !== 'YOUR_SECRET_TOKEN') {

    //     $connection->send(
    //         json_encode([
    //             'success' => false,
    //             'message' => 'Unauthorized'
    //         ])
    //     );

    //     return;
    // }

    if ($request->method() !== 'POST' || $request->path() !== '/internal/seat-expired') {

        $connection->send(
            json_encode([
                'success' => false,
                'message' => 'Not found'
            ])
        );

        return;
    }

    $body = $request->rawBody();

    $data = json_decode($body, true);

    if (!$data || empty($data['seats'])) {

        $connection->send(
            json_encode([
                'success' => false,
                'message' => 'Invalid payload'
            ])
        );

        return;
    }

    foreach ($data['seats'] as $seat) {

        $tripId = (int) $seat['trip_id'];
        $seatId = (int) $seat['seat_id'];

        if (!isset($tripConnections[$tripId])) {
            continue;
        }

        $message = json_encode([
            'action' => 'seat_status_change',
            'trip_id' => $tripId,
            'seat_id' => $seatId,
            'status' => 'available'
        ]);

        foreach ($tripConnections[$tripId]as $client) {
            $client->send($message);
        }
    }

    $connection->send(
        json_encode([
            'success' => true
        ])
    );
};

// Internal port that only your app can reach (127.0.0.1)
// $ws->onWorkerStart = function () use ($ws) {
//     $inner = new Worker('text://127.0.0.1:5678');

//     $inner->onMessage = function ($conn, $data) use ($ws) {
//         $msg     = json_decode($data, true);
//         $payload = json_encode($msg['data'] ?? null);

//         foreach ($ws->connections as $client) {
//             $client->send($payload);
//         }

//         $conn->send('ok');
//     };

//     $inner->listen();
// };

Worker::runAll();

?>