<?php 


$config = [
    'host' => 'localhost',
    'port' => 3306,
    'dbname' => 'vehicle_bookings',
    'username' => 'sample',
    'password' => 'Zxcvb123@'
];


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
    exit(0);
}

$updateSql = "
        UPDATE trip_seats
        SET
            status = 'available',
            hold_expires_at = NULL
        WHERE status = 'hold'
          AND locked_until IS NOT NULL
          AND locked_until <= NOW()
    ";

$updateStmt = db()->prepare($updateSql);
$updateStmt->execute();

$releasedCount = $updateStmt->rowCount();

echo date('Y-m-d H:i:s'). " - Released {$releasedCount} seats.\n";

$ch = curl_init('http://127.0.0.1:8081/internal/seat-expired');

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'seats' => $expiredSeats
    ]),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Internal-Token' => 'sk_test_7f9K2mQ8xP4vL6nR3tY1wZ5a'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
]);

$response = curl_exec($ch);

    if ($response === false) {
        throw new Exception('Workerman notification failed: ' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($httpCode !== 200) {

        throw new Exception("Workerman returned HTTP {$httpCode}");
    }

    echo date('Y-m-d H:i:s'). " - Workerman notified successfully.\n";
}
catch (Throwable $e) {
    echo date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage(). "\n";
    exit(1);
}


?>