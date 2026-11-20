<?php

namespace App\Controllers;

// use Framework\Database;

use DateTime;
use Framework\Session;
use Framework\TripDb;

class TripsController
{
    protected TripDb $db;

    public function __construct()
    {
        $config = require basePath('config/db2.php');
        $this->db = new TripDb($config);
    }

    /**
     * GET /trips
     * List all trips with route, stand and vehicle details.
     */
    public function index()
    {
        $terminal = $this->getTerminal();

        $trips = $this->db->query(
            "SELECT
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
            "
        )->fetchAll();

        loadView('trips/home', [
            'trips' => $trips,
            'terminal' => $terminal,
        ]);
    }

    private function getTerminal()
    {
        return $this->db->query(
            "SELECT terminal_id, terminal_name FROM terminals ORDER BY terminal_id LIMIT 1"
        )->fetch();
    }

    /**
     * GET /trips/create
     * Show the form with dropdowns filled from the database.
     */
    public function create()
    {
        loadView('trips/create', $this->formData());
    }

    /**
     * POST /trips/create
     * Validate the form and insert the trip.
     */
    public function store()
    {
        $trip = [
            'route_id'        => trim($_POST['route_id'] ?? ''),
            'stand_id'        => trim($_POST['stand_id'] ?? ''),
            'vehicle_id'      => trim($_POST['vehicle_id'] ?? ''),
            'departure_time'  => trim($_POST['departure_time'] ?? ''),
            'fare'            => trim($_POST['fare'] ?? ''),
            //'available_seats' => trim($_POST['available_seats'] ?? ''),
        ];

        // 1. Basic field validation
        $errors = $this->validate($trip);

        // 2. Check the chosen route, stand and vehicle really exist
        $route = $stand = $vehicle = null;

        if (empty($errors)) {
            $route = $this->db->query(
                "SELECT route_id, route_name FROM routes WHERE route_id = :id",
                ['id' => $trip['route_id']]
            )->fetch();

            $stand = $this->db->query(
                "SELECT stand_id, stand_name FROM stands WHERE stand_id = :id",
                ['id' => $trip['stand_id']]
            )->fetch();

            $vehicle = $this->db->query(
                "SELECT vehicle_id, total_seats FROM vehicles WHERE vehicle_id = :id",
                ['id' => $trip['vehicle_id']]
            )->fetch();

            if (!$route)   $errors['route_id']   = 'Selected route does not exist.';
            if (!$stand)   $errors['stand_id']   = 'Selected stand does not exist.';
            if (!$vehicle) $errors['vehicle_id'] = 'Selected vehicle does not exist.';
        }

        // 3. Business rules
        if (empty($errors)) {

            // The stand must be at the route's departure terminal
            // if ((int) $stand->terminal_id !== (int) $route->from_terminal_id) {
            //     $errors['stand_id'] = 'Choose a stand at the departure terminal of this route.';
            // }

            // Seats cannot be more than the vehicle holds
            // if ((int) $trip['available_seats'] > (int) $vehicle->seats) {
            //     $errors['available_seats'] = "This vehicle has only {$vehicle->seats} seats.";
            // }

            // The vehicle cannot have another trip within 2 hours of this one
            $clash = $this->db->query(
                "SELECT trip_id FROM trips
                 WHERE vehicle_id = :vehicle_id
                   AND status = 'scheduled'
                   AND departure_time BETWEEN DATE_SUB(:time1, INTERVAL 2 HOUR)
                                          AND DATE_ADD(:time2, INTERVAL 2 HOUR)",
                [
                    'vehicle_id' => $trip['vehicle_id'],
                    'time1'      => $this->toMysqlDate($trip['departure_time']),
                    'time2'      => $this->toMysqlDate($trip['departure_time']),
                ]
            )->fetch();

            if ($clash) {
                $errors['vehicle_id'] = 'This vehicle already has a trip within 2 hours of that time.';
            }
        }

        // 4. Show the form again with errors and the old values
        if (!empty($errors)) {
            loadView('trips/create', array_merge($this->formData(), [
                'errors' => $errors,
                'trip'   => $trip
            ]));
            exit();
        }

        $conn = $this->db->getConn();

        try {

            // 5. Insert the trip
            $sql = "INSERT INTO trips (route_id, vehicle_id, departure_stand_id, departure_time, fare, status)
                VALUES (:route_id, :vehicle_id, :stand_id, :departure_time, :fare, 'scheduled')";

            $this->db->query($sql, [
                'route_id'        => $trip['route_id'],
                'vehicle_id'      => $trip['vehicle_id'],
                'stand_id'        => $trip['stand_id'],
                'departure_time'  => $this->toMysqlDate($trip['departure_time']),
                'fare'            => $trip['fare'],
                //'available_seats' => $trip['available_seats'],
                //'created_by'      => Session::get('admin')['userId'] ?? null,
            ]);

            $tripId = $conn->lastInsertId();

            // Every active seat of the vehicle becomes an available seat on this trip
            $this->db->query(
                "INSERT INTO trip_seats (trip_id, vehicle_seat_id, seat_number, fare, status)
                SELECT :trip_id, vehicle_seat_id, seat_number, :fare, 'available'
                FROM vehicle_seats
                WHERE vehicle_id = :vehicle_id AND is_active = 1",
                [
                    'trip_id'    => $tripId,
                    'fare'       => $trip['fare'],
                    'vehicle_id' => $trip['vehicle_id'],
                ]
            );
        } catch (\Throwable $e) {
            $conn->rollBack();

            loadView('trips/create', array_merge($this->formData(), [
                'errors' => ['general' => 'Could not save the trip. Please try again.'],
                'trip'   => $trip
            ]));
            exit();
        }

        redirect('/trips');
    }


    public function show()
    {
        $id = $_GET['id'] ?? '';
        $params = [
            'id' => $id
        ];

        $trip = $this->db->query(

            "SELECT
                tr.trip_id,
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
                    WHERE ts.trip_id = tr.trip_id
                    AND ts.status = 'available') AS available_seats
            FROM trips tr
            JOIN routes r ON tr.route_id = r.route_id
            JOIN stands s ON s.stand_id = tr.departure_stand_id
            JOIN vehicles v ON v.vehicle_id = tr.vehicle_id
            WHERE tr.status = 'scheduled'
            AND tr.trip_id = :id
            ORDER BY tr.departure_time DESC",

            $params

        )->fetch();

        $sql =
            "SELECT
                ts.trip_seat_id,
                tr.trip_id,
                tr.vehicle_id, 
                tr.departure_time,
                ts.seat_number,
                ts.status,
                ts.fare
            FROM trip_seats ts JOIN trips tr ON tr.trip_id = ts.trip_id   
            WHERE tr.trip_id = :id";

        $seats = $this->db->query($sql, $params)->fetchAll();

        loadView('trips/show', [
            'seats' => $seats,
            'trip_id' => $id,
            'trip' => $trip
        ]);
    }

    function failTo(string $url, string $message): void
    {
        Session::set('checkout_error', $message);
        header('Location: ' . $url);
        exit;
    }

    function checkout(){

    }

    function booking()
    {
        $conn = $this->db->getConn();

        $user      = Session::get('user');
        $userId    = $user['id'] ?? null;

        $tripId = (int) ($_POST['trip_id'] ?? 0);
        $back   = '/trip?id=' . $tripId;

        $seatIds = array_values(array_unique(array_filter(array_map('intval', $_POST['seat_ids'] ?? []))));
        
        if (!$tripId || !$seatIds || count($seatIds) > 4) {
            $this->failTo($back, 'Choose between 1 and 4 seats.');
        }

        $respond = function (int $status, array $body) {
            http_response_code($status);
            echo json_encode($body);
            exit;
        };

        $bookingId = 0;

        $conn->beginTransaction();

        try {
            $in   = implode(',', array_fill(0, count($seatIds), '?'));
            $stmt = $conn->prepare("
                SELECT ts.trip_seat_id, tr.fare
                FROM trip_seats ts
                JOIN trips tr ON tr.trip_id = ts.trip_id
                WHERE ts.trip_id = ? AND ts.trip_seat_id IN ($in)
                AND ts.status = 'hold' AND ts.held_by = ? AND ts.locked_until >= NOW()
                FOR UPDATE
            ");

            $stmt->execute([$tripId, ...$seatIds]);
            $rows = $stmt->fetchAll();

            if (count($rows) !== count($seatIds)) {
                $conn->rollBack();
                $lost = array_values(array_diff($seatIds, array_column($rows, 'trip_seat_id')));

                $respond(409, [
                    'message'    => 'Some of your seats are no longer held. Please choose again.',
                    'lost_seats' => $lost,
                ]);

                $amount = (float) $rows[0]['fare'] * count($rows);

                $stmt = $conn->prepare("
                    INSERT INTO bookings (trip_id, user_id, amount, status, expires_at)
                    VALUES (?, ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL 10 MINUTE))
                ");

                $stmt->execute([
                    $tripId,
                    $userId,
                    $amount
                ]);

                $bookingId = (int) $conn->lastInsertId();

                $link = $conn->prepare("INSERT INTO booking_seats (booking_id, trip_seat_id) VALUES (?, ?, ?)");
                foreach ($seatIds as $seatId) {
                    $link->execute([$bookingId, $seatId, $rows[0]['fare']]);
                }

                // Extend the hold to cover the checkout time
                $stmt = $conn->prepare("
                    UPDATE trip_seats SET locked_until = DATE_ADD(NOW(), INTERVAL 10 MINUTE)
                    WHERE trip_id = ? AND trip_seat_id IN ($in) AND held_by = ?
                ");
        
                $stmt->execute([$tripId, ...$seatIds]);

                $conn->commit();
            }
        } catch (\Throwable $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            error_log('[checkout] ' . $e);
            $respond(500, ['message' => 'Could not start checkout. Please try again.']);
        }

        Session::set('booking_id', $bookingId);   
        $respond(200, ['redirect' => '/checkout?booking=' . $bookingId]);
    }

    /**
     * Data for the dropdowns on the create form.
     */
    private function formData(): array
    {
        $routes = $this->db->query(
            "SELECT r.route_id, r.route_name FROM routes r 
                JOIN terminals t ON t.city_id = r.origin_city_id"
        )->fetchAll();

        $stands = $this->db->query(
            "SELECT s.stand_id, s.stand_name
                FROM stands s
                JOIN terminals t ON t.terminal_id = s.terminal_id"
        )->fetchAll();

        $vehicles = $this->db->query(
            "SELECT vehicle_id , vehicle_number, total_seats FROM vehicles ORDER BY vehicle_number"
        )->fetchAll();

        return [
            'routes'   => $routes,
            'stands'   => $stands,
            'vehicles' => $vehicles,
            'errors'   => [],
            'trip'     => []
        ];
    }

    /**
     * Field-level validation. Returns ['field' => 'message'].
     */
    private function validate(array $trip): array
    {
        $errors = [];

        foreach (['route_id' => 'route', 'stand_id' => 'stand', 'vehicle_id' => 'vehicle'] as $field => $label) {
            if (filter_var($trip[$field], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                $errors[$field] = "Please select a $label.";
            }
        }

        $departure = DateTime::createFromFormat('Y-m-d\TH:i', $trip['departure_time']);
        if (!$departure) {
            $errors['departure_time'] = 'Please enter a valid departure date and time.';
        } elseif ($departure <= new DateTime()) {
            $errors['departure_time'] = 'Departure time must be in the future.';
        }

        if (!is_numeric($trip['fare']) || (float) $trip['fare'] <= 0) {
            $errors['fare'] = 'Fare must be a number greater than zero.';
        }

        // if (filter_var($trip['available_seats'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
        //     $errors['available_seats'] = 'Seats must be a whole number of at least 1.';
        // }

        return $errors;
    }

    /**
     * Convert the HTML datetime-local value (2026-11-20T14:30) to MySQL format.
     */
    private function toMysqlDate(string $value): string
    {
        $date = DateTime::createFromFormat('Y-m-d\TH:i', $value);
        return $date ? $date->format('Y-m-d H:i:s') : $value;
    }
}
