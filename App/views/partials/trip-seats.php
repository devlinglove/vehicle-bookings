<?php

use Framework\Session;

/**
 * @var object $booking      booking_id, amount, status
 * @var object $trip         trip_id, route_name, stand_name, departure_time, vehicle_*, fare
 * @var array  $seats        selected seats: trip_seat_id, seat_number, (optional) gender
 */

$fare      = (float) $trip->fare;
$amount    = (float) $booking->amount;
$seatCount = count($seats);


?>

<div class="space-y-2 text-sm text-gray-600">
    <div class="flex justify-between">
        <span>Fare per seat</span>
        <span class="tabular-nums">Rs. <?= number_format($fare) ?></span>
    </div>
    <div class="flex justify-between">
        <span>Seats</span>
        <span class="tabular-nums">× <?= $seatCount ?></span>
    </div>
</div>

<div class="mt-4 flex items-baseline justify-between border-t border-gray-100 pt-4">
    <span class="font-semibold text-gray-900">Total</span>
    <span class="text-3xl font-bold tabular-nums text-gray-900">Rs. <?= number_format($amount) ?></span>
</div>