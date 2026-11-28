<?php

namespace App\Controllers;

use Exception;
use finfo;
use Framework\Database;
use Framework\Pay;
use Framework\PayFastService;
use Framework\SafePay;
use Framework\Session;
use Framework\TripDb;
use Framework\UserValidator;
use PDO;
use Throwable;

class HomeController
{
    protected TripDb $db;
    protected SafePay $paymentService;
    protected mixed $cloudinary;
    

    public function __construct()
    {
        $config = require basePath('config/db2.php');
        $this->db = new TripDb($config);
        $this->paymentService = new SafePay();
        //$this->$cloudinary = require basePath('config/cloudinary.php');

    }

    public function index()
    {
        loadView('home');
    }

    function bookings()
    {
        $conn = $this->db->getConn();

        $user      = Session::get('user');
        $userId    = $user['userId'] ?? null;

        $tripId = (int) ($_POST['trip_id'] ?? 0);
        $holder = Session::get('hold_token');

        //dd($holder);

        $back   = '/trip?id=' . $tripId;

        $seatIds = array_values(array_unique(array_filter(array_map('intval', $_POST['seat_ids'] ?? []))));
        
        if (!$tripId || !$seatIds || count($seatIds) > 4) {
            failTo($back, 'Choose between 1 and 4 seats.');
            //exit(1);
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
                AND ts.status = 'hold' AND held_by = ? AND ts.locked_until >= NOW()
                FOR UPDATE
            ");

            $stmt->execute([$tripId, ...$seatIds, $holder]);
            $rows = $stmt->fetchAll();

            if (count($rows) !== count($seatIds)) {

                $conn->rollBack();
                $lost = array_values(array_diff($seatIds, array_column($rows, 'trip_seat_id')));

                $respond(409, [
                    'message'    => 'Some of your seats are no longer held. Please choose again.',
                    'lost_seats' => $lost,
                ]);
            }

                $fareBySeat = [];
                $amount = 0;

                foreach ($rows as $row) {
                    $fareBySeat[$row->trip_seat_id] = $row->fare;
                    $amount += $row->fare;
                }

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

                $link = $conn->prepare("INSERT INTO booking_seats (booking_id, trip_seat_id, fare) VALUES (?, ?, ?)");

               foreach ($seatIds as $seatId) {
                    $fare = $fareBySeat[$seatId];
                    $link->execute([$bookingId, $seatId, $fare]);
                }

                // Extend the hold to cover the checkout time
                $stmt = $conn->prepare("
                    UPDATE trip_seats SET locked_until = DATE_ADD(NOW(), INTERVAL 10 MINUTE)
                    WHERE trip_id = ? AND trip_seat_id IN ($in) AND held_by = ?
                ");
        
                $stmt->execute([$tripId, ...$seatIds, $holder]);

                $conn->commit();
            
        } catch (\Throwable $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            error_log('[checkout] ' . $e);
            $respond(500, ['message' => 'Could not start checkout. Please try again.' . $e->getMessage()]);
        }

        Session::set('booking_id', $bookingId);   
        redirect('/checkout?booking=' . $bookingId . '&trip_id=' . $tripId);

        
    }

    function getBookingById($bookingId)
    {
        $sampleBookings = [
            4213 => [
                'id' => 4213,
                'voucher_number' => 'VB-2611-004213',
                'amount' => 5000,

                'route_from' => 'Lahore',
                'route_to' => 'Islamabad',

                'departure' => 'Sat, 28 Nov',
                'departure_time' => '09:30 AM',

                'seats' => ['12', '13'],
                'fare_per_seat' => 2500,

                'pay_by' => '6:00 PM today',

                'bank' => '[YOUR BANK]',
                'account_title' => '[ACCOUNT TITLE]',
                'iban' => '[YOUR IBAN]',

                'support_number' => '[SUPPORT NUMBER]'
            ]
        ];

        return $sampleBookings[$bookingId] ?? null;
    }


    private function getBooking(int $bookingId, int $userId)
    {
        $stmt = $this->db->getConn()->prepare("
            SELECT booking_id, trip_id, user_id, amount, status, TIMESTAMPDIFF(SECOND, NOW(), expires_at) AS seconds_left
            FROM bookings
            WHERE booking_id = ?
        ");

        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();

        $ownsBooking = $booking && ((int) Session::get('booking_id') === $bookingId || ($userId && (int) $booking->user_id === (int) $userId));

        if (!$ownsBooking || $booking->status !== 'pending') {
            return failTo('/trips', 'This booking is no longer available.');
        }

        return $booking;
    }

    private function getTripDetails(int $tripId)
    {
        $stmt = $this->db->getConn()->prepare("
            SELECT 
                tr.trip_id, 
                tr.departure_time, 
                tr.fare,
                r.route_name, 
                s.stand_name,
                v.vehicle_name, 
                v.vehicle_number, 
                v.vehicle_type
            FROM trips tr
            JOIN routes r   ON r.route_id   = tr.route_id
            JOIN stands s   ON s.stand_id   = tr.departure_stand_id
            JOIN vehicles v ON v.vehicle_id = tr.vehicle_id
            WHERE tr.trip_id = ?
        ");

        $stmt->execute([$tripId]);
        return $trip = $stmt->fetch();
    }

    private function getBookingSeats(int $bookingId, string $holder)
    {
        $stmt = $this->db->getConn()->prepare("
            SELECT ts.trip_seat_id, ts.seat_number
            FROM booking_seats bs
            JOIN trip_seats ts ON ts.trip_seat_id = bs.trip_seat_id
            WHERE bs.booking_id = ?
            AND ts.status = 'hold'
            AND ts.held_by = ?
            ORDER BY ts.seat_number
        ");
        $stmt->execute([$bookingId, $holder]);
        $seats = $stmt->fetchAll();

        return $seats;
    }

    private function getTripSeats(int $tripId, string $holderUser)
    {
        $stmt = $this->db->getConn()->prepare("
            SELECT ts.trip_seat_id, ts.seat_number
            FROM trip_seats ts
            WHERE ts.trip_id = ?
            AND ts.status = 'hold'
            AND ts.held_by = ?
            ORDER BY ts.seat_number
        ");
        $stmt->execute([$tripId, $holderUser]);
        $seats = $stmt->fetchAll();

        return $seats;
    }



    function checkout(){
        $bookingId = (int) ($_GET['booking'] ?? 0);
        $holder    = Session::get('hold_token');
        $userId    = Session::get('user')['userId'] ?? null;


        // $stmt = $this->db->getConn()->prepare("
        //     SELECT booking_id, trip_id, user_id, amount, status, TIMESTAMPDIFF(SECOND, NOW(), expires_at) AS seconds_left
        //     FROM bookings
        //     WHERE booking_id = ?
        // ");

        // $stmt->execute([$bookingId]);
        // $booking = $stmt->fetch();

        // $ownsBooking = $booking && ((int) Session::get('booking_id') === $bookingId || ($userId && (int) $booking->user_id === (int) $userId));

        // if (!$ownsBooking || $booking->status !== 'pending') {
        //     return failTo('/trips', 'This booking is no longer available.');
        // }

        $booking = $this->getBooking($bookingId, $userId);

        $tripUrl = '/trip?id=' . (int) $booking->trip_id;

        if ((int) $booking->seconds_left <= 0) {
            return failTo($tripUrl, 'Your seat hold expired. Please choose your seats again.');
        }

        // 3. Trip details for the ticket summary
        // $stmt = $this->db->getConn()->prepare("
        //     SELECT 
        //         tr.trip_id, 
        //         tr.departure_time, 
        //         tr.fare,
        //         r.route_name, 
        //         s.stand_name,
        //         v.vehicle_name, 
        //         v.vehicle_number, 
        //         v.vehicle_type
        //     FROM trips tr
        //     JOIN routes r   ON r.route_id   = tr.route_id
        //     JOIN stands s   ON s.stand_id   = tr.departure_stand_id
        //     JOIN vehicles v ON v.vehicle_id = tr.vehicle_id
        //     WHERE tr.trip_id = ?
        // ");

        // $stmt->execute([$booking->trip_id]);
        // $trip = $stmt->fetch();

        $trip = $this->getTripDetails($booking->trip_id);

        // 4. Seats in this booking, still held by this session
        // $stmt = $this->db->getConn()->prepare("
        //     SELECT ts.trip_seat_id, ts.seat_number
        //     FROM booking_seats bs
        //     JOIN trip_seats ts ON ts.trip_seat_id = bs.trip_seat_id
        //     WHERE bs.booking_id = ?
        //     AND ts.status = 'hold'
        //     AND ts.held_by = ?
        //     ORDER BY ts.seat_number
        // ");
        // $stmt->execute([$bookingId, $holder]);
        // $seats = $stmt->fetchAll();


        $seats = $this->getTripSeats($booking->trip_id, $holder);

        if (!$trip || !$seats) {
            return failTo($tripUrl, 'Your seats are no longer held. Please choose again.');
        }

            // 5. Errors and old input from a failed payment submit (flash values)
            $errors = Session::get('checkout_errors') ?? [];
            $old    = Session::get('checkout_old') ?? [];
            Session::clear('checkout_errors');
            Session::clear('checkout_old');

            // 6. Give the view everything it needs
            loadView('checkout', [
                'booking'     => $booking,
                'trip'        => $trip,
                'seats'       => $seats,
                'secondsLeft' => (int) $booking->seconds_left,
                'errors'      => $errors,
                'old'         => $old,
            ]);
    }


    // private function getBookingById(int $bookingId)
    // {
    //     $stmt = $this->db->getConn()->prepare("
    //         SELECT booking_id, trip_id, user_id, amount, status, TIMESTAMPDIFF(SECOND, NOW(), expires_at) AS seconds_left
    //         FROM bookings
    //         WHERE booking_id = ?
    //     ");

    //     $stmt->execute([$bookingId]);
    //     $booking = $stmt->fetch();

    //     return $booking;
    // }

    

    public function voucher(){
        $bookingId = (int)($_GET['booking_id'] ?? 4213);

        $booking = $this->getBookingById($bookingId);

        if (!$booking) {
            http_response_code(404);
        }
        
        loadView('payments/voucher', [
            'booking' => $booking
        ]);

    }

    public function selection()
    {
        $bookingId = $_GET['booking_id'] ?? null;
        $tripId = $_GET['trip_id'] ?? null;
        $holder    = Session::get('hold_token');
        $user      = Session::get('user');
        $userId    = $user['userId'] ?? null;

        $booking = $this->getBooking($bookingId, $userId);
        $trip = $this->getTripDetails($tripId);

        $seats = $this->getTripSeats($tripId, $holder);

        if (!$bookingId) {
           return failTo('/trips', 'We could not find that payment.');
        }

        loadView('payments/selection', [
            'trip' => $trip,
            'booking' => $booking,
            'seats' => $seats
        ]);
    }

    // function failTo(string $url, string $message): void
    // {
    //     Session::set('checkout_error', $message);
    //     header('Location: ' . $url);
    //     exit;
    // }

    public function success()
    {
        $tracker = $_GET['tracker'] ?? null;

        if (!$tracker) {
           return failTo('/trips', 'We could not find that payment.');
        }

        $pdo = $this->db->getConn();

        // 1. Find OUR payment first, so unknown trackers never reach Safepay's API
        $stmt = $pdo->prepare("SELECT payment_id, booking_id, status FROM payments WHERE tracker = ?");
        $stmt->execute([$tracker]);
        $payment = $stmt->fetch();

        if (!$payment) {
            return failTo('/trips', 'We could not find that payment.');
        }

        // 2. Already processed (page refreshed, or the webhook got there first)
        if ($payment->status === 'paid') {
            return $this->showResult($pdo, (int) $payment->booking_id, $tracker);
        }

        $state = '';

        try {
            $state = $this->paymentService->fetchTrackerInfo($tracker);
        } catch (\Throwable $e) {
            error_log('[safepay] tracker check failed: ' . $e->getMessage());
            throw $e;
        }

        if ($state === 'TRACKER_ENDED') {
            try {
                $this->markPaid($pdo, (int) $payment->booking_id, $tracker);
            } catch (\Throwable $e) {
                error_log('[safepay] markPaid failed for booking ' . $payment->booking_id . ': ' . $e);
            }
        }

        return $this->showResult($pdo, (int) $payment->booking_id, $tracker);

    }

    private function markPaid(PDO $pdo, int $bookingId, string $tracker): string
    {
        $pdo->beginTransaction();

        try {
            // Lock the booking so the success page and the webhook can't both process it
            $stmt = $pdo->prepare("
            SELECT booking_id, status, hold_token
            FROM bookings WHERE booking_id = ? FOR UPDATE
        ");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch();

            if (!$booking) {
                throw new \RuntimeException("Booking {$bookingId} not found");
            }

            if (in_array($booking->status, ['paid', 'refund_required'], true)) {
                $pdo->commit();
                return $booking->status;
            }

            // Lock this booking's seats
            $stmt = $pdo->prepare("
            SELECT ts.trip_seat_id, ts.status, ts.held_by
                FROM booking_seats bs
                JOIN trip_seats ts ON ts.trip_seat_id = bs.trip_seat_id
                WHERE bs.booking_id = ?
            FOR UPDATE
        ");
            $stmt->execute([$bookingId]);
            $seats = $stmt->fetchAll();

             // Validate all seats
            $allOurs = !empty($seats);

            foreach ($seats as $seat) {
                if ($seat->status !== 'available' && !($seat->status === 'hold' && $seat->held_by === $booking->hold_token)) {
                    $allOurs = false;
                    break;
                }
            }

            // The money was received either way
            $pdo->prepare("UPDATE payments SET status = 'paid', paid_at = NOW() WHERE tracker = ?")
                ->execute([$tracker]);
            
            //refund scenario    
            // if (!$allOurs) {
            //     $pdo->prepare("UPDATE bookings SET status = 'refund_required' WHERE booking_id = ?")
            //         ->execute([$bookingId]);

            //     $pdo->prepare("
            //     UPDATE trip_seats ts
            //         JOIN booking_seats bs ON bs.trip_seat_id = ts.trip_seat_id
            //         SET ts.status = 'available', ts.held_by = NULL, ts.locked_until = NULL
            //     WHERE bs.booking_id = ? AND ts.status = 'hold' AND ts.held_by = ?
            // ")->execute([$bookingId, $booking->hold_token]);

            //     $pdo->commit();
            //     error_log("[safepay] REFUND NEEDED: booking {$bookingId} paid but seats were taken");
                
            //     return 'refund_required';
            // }

            $pdo->prepare("
                UPDATE trip_seats ts
                    JOIN booking_seats bs ON bs.trip_seat_id = ts.trip_seat_id
                    SET ts.status = 'booked', ts.held_by = NULL, ts.locked_until = NULL
                WHERE bs.booking_id = ?
            ")->execute([$bookingId]);

            $pdo->prepare("
                UPDATE bookings SET status = 'paid'
                WHERE booking_id = ?
            ")->execute([$bookingId]);

            $pdo->commit();

            return 'paid';
        
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private function showResult(PDO $pdo, int $bookingId, string $tracker)
    {
        $stmt = $pdo->prepare("
            SELECT b.booking_id, b.trip_id, b.status, b.amount, r.route_name, tr.departure_time
            FROM bookings b
            JOIN trips tr ON tr.trip_id = b.trip_id
            JOIN routes r ON r.route_id = tr.route_id
            WHERE b.booking_id = ?
        ");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch(PDO::FETCH_OBJ);

        $stmt = $pdo->prepare("
            SELECT ts.seat_number
            FROM booking_seats bs
            JOIN trip_seats ts ON ts.trip_seat_id = bs.trip_seat_id
            WHERE bs.booking_id = ?
            ORDER BY ts.seat_number
        ");
        $stmt->execute([$bookingId]);

        loadView('payments/result', [
            'booking'     => $booking,
            'seatNumbers' => $stmt->fetchAll(PDO::FETCH_COLUMN),
            'basketId'    => $tracker,   // shown as "Reference"
        ]);
    }



    public function cancel()
    {
        echo "Payment was cancelled by the customer.";
    }

    function initiatePayment(){

        $errors = UserValidator::validate($_POST);

        // if (!empty($errors)) {
        //     loadView('transporter/update', [
        //         'errors' => $errors
        //     ]);
        //     exit();
        // }

        $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);

        if (!$bookingId) {
            $errors['booking_id'][] = 'Invalid booking id';
        }

        $params = [
            'id' => $bookingId
        ];

        $sql =
            "SELECT 
                booking_id, 
                trip_id, 
                user_id, 
                amount, 
                status FROM 
                bookings 
            WHERE booking_id = :id
            ";

        $booking = $this->db->query($sql, $params)->fetch();

        if(!$booking){
            $errors['booking_id'][] = 'Order not found';
        }

        if ($booking->status !== 'pending') {
           $errors['booking_id'][] = 'Order cannot be paid right now.';
        }


        redirect('/checkout/selection?booking_id=' . $bookingId . '&trip_id=' . $booking->trip_id);

    }


    function pay(){

        $errors = UserValidator::validate($_POST);

        // if (!empty($errors)) {
        //     loadView('transporter/update', [
        //         'errors' => $errors
        //     ]);
        //     exit();
        // }

        $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);

        if (!$bookingId) {
            $errors['booking_id'][] = 'Invalid booking id';
        }

        $params = [
            'id' => $bookingId
        ];

        $sql =
            "SELECT 
                booking_id, 
                trip_id, 
                user_id, 
                amount, 
                status FROM 
                bookings 
            WHERE booking_id = :id
            ";

        $booking = $this->db->query($sql, $params)->fetch();

        if(!$booking){
            $errors['booking_id'][] = 'Order not found';
        }

        if ($booking->status !== 'pending') {
           $errors['booking_id'][] = 'Order cannot be paid right now.';
        }

        try {
            $url = $this->paymentService->createPayment($booking);
            redirect($url);
        } catch (Throwable $e) {
            error_log($e->getMessage());
            http_response_code(500);
            echo 'Unable to initiate payment.' . $e->getMessage();
        }
    }


    function upload()
    {

        header('Content-Type: application/json');

        try {

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                throw new Exception('Method not allowed');
            }

            if (!isset($_FILES['file'])) {
                http_response_code(400);
                throw new Exception('No file received');
            }

            $file = $_FILES['file'];
            $bookingId = $_POST['booking_id'] ?? null;

            if ($file['error'] !== UPLOAD_ERR_OK) {
                http_response_code(400);
                throw new Exception('File upload failed');
            }

            // Maximum 5 MB
            if ($file['size'] > 5 * 1024 * 1024) {
                http_response_code(400);
                throw new Exception('Maximum file size is 5 MB');
            }

            // Validate actual MIME type
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);

            $allowedTypes = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];

            if (!in_array($mime, $allowedTypes, true)) {
                http_response_code(400);
                throw new Exception('Invalid image format');
            }

            // Upload to Cloudinary
            $result = cloudinary()->uploadApi()->upload(
                $file['tmp_name'],
                [
                    'folder' => 'my-app/uploads',
                    'resource_type' => 'image'
                ]
            );


            $url = $result['secure_url'];
            $publicId = $result['public_id'];
            $format = $result['format'];

            $stmt = $this->db->getConn()->prepare("
                INSERT INTO attachments
                (file_url, public_id, file_type)
                VALUES (?, ?, ?)
            ");

            $stmt->execute([
                $url,
                $publicId,
                $format
            ]);


            $stmt = $this->db->getConn()->prepare("
                UPDATE bookings
                SET attachment_public_id = ?,
                    attachment_url = ?
                WHERE booking_id = ?
            ");

            $stmt->execute([
                $result['public_id'],
                $result['secure_url'],
                $bookingId
            ]);



            echo json_encode([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => [
                    'url' => $result['secure_url'],
                    'public_id' => $result['public_id'],
                    'format' => $result['format']
                ]
            ]);
            
        } catch (Exception $e) {

            if (http_response_code() < 400) {
                http_response_code(500);
            }

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
