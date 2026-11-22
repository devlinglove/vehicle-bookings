<?php

/**
 * Payment result page. Everything comes from the database, never from the URL.
 *
 * @var object|false $booking      booking_id, trip_id, status, amount, route_name, departure_time
 * @var array        $seatNumbers
 * @var string       $basketId     reference shown to the customer (Safepay tracker)
 */

if (!$booking) {
    Framework\Session::set('checkout_error', 'We could not find that booking.');
    header('Location: /trips');
    exit;
}

$status  = $booking->status;
$waiting = in_array($status, ['pending', 'awaiting_payment'], true);
$paid    = $status === 'paid';
$failed  = in_array($status, ['failed', 'expired', 'cancelled'], true);

?>

<?php loadPartialView('header'); ?>
<?php loadPartialView('navbar'); ?>

    

<?php loadPartialView('footer'); ?>