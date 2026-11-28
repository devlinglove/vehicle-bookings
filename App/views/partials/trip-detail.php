<?php

use Framework\Session;

/**
 * @var object $booking      booking_id, amount, status
 * @var object $trip         trip_id, route_name, stand_name, departure_time, vehicle_*, fare
 * @var array  $seats        selected seats: trip_seat_id, seat_number, (optional) gender
 */

// "Lahore - Islamabad" → from / to. Falls back to the full route name.
$parts = preg_split('/\s*(?:-|–|→|\bto\b)\s*/iu', (string) $trip->route_name, 2);
$from  = count($parts) === 2 ? trim($parts[0]) : null;
$to    = count($parts) === 2 ? trim($parts[1]) : null;

$departs   = strtotime($trip->departure_time);
$fare      = (float) $trip->fare;
$amount    = (float) $booking->amount;
$seatCount = count($seats);

$vehicle = '';
if (!empty($trip->vehicle_number)) {
    $vehicle = (($trip->vehicle_name ?? '') ?: 'Vehicle') . ' #' . $trip->vehicle_number;
}
?>

<div class="rounded-t-2xl bg-white p-6">
    <div class="flex items-center justify-between text-sm text-gray-500">
        <span>Booking #<?= (int) $booking->booking_id ?></span>
        <span><?= date('D, d M Y', $departs) ?></span>
    </div>

    <?php if ($from && $to) : ?>
        <div class="mt-5 flex items-center gap-3">
            <div class="min-w-0">
                <p class="truncate text-2xl font-bold text-gray-900"><?= htmlspecialchars($from) ?></p>
                <p class="truncate text-sm text-gray-500"><?= htmlspecialchars($trip->stand_name ?? '') ?></p>
            </div>

            <div class="flex flex-1 items-center gap-1 text-blue-600" aria-hidden="true">
                <span class="h-2 w-2 shrink-0 rounded-full border-2 border-blue-600"></span>
                <span class="flex-1 border-t-2 border-dashed border-blue-200"></span>
                <svg viewBox="0 0 24 24" class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8">
                    <rect x="3" y="4" width="18" height="13" rx="2.5" />
                    <path d="M3 10h18M7.5 17v2.5M16.5 17v2.5" />
                    <circle cx="7.5" cy="13.5" r=".8" fill="currentColor" />
                    <circle cx="16.5" cy="13.5" r=".8" fill="currentColor" />
                </svg>
                <span class="flex-1 border-t-2 border-dashed border-blue-200"></span>
                <span class="h-2 w-2 shrink-0 rounded-full bg-blue-600"></span>
            </div>

            <div class="min-w-0 text-right">
                <p class="truncate text-2xl font-bold text-gray-900"><?= htmlspecialchars($to) ?></p>
                <p class="text-sm text-gray-500">Arrival</p>
            </div>
        </div>
    <?php else : ?>
        <p class="mt-5 text-2xl font-bold text-gray-900"><?= htmlspecialchars($trip->route_name) ?></p>
        <p class="text-sm text-gray-500"><?= htmlspecialchars($trip->stand_name ?? '') ?></p>
    <?php endif; ?>

    <dl class="mt-6 grid grid-cols-2 gap-x-4 gap-y-4 text-sm">
        <div>
            <dt class="text-gray-500">Departs</dt>
            <dd class="mt-0.5 text-lg font-bold tabular-nums text-gray-900"><?= date('h:i A', $departs) ?></dd>
        </div>
        <div>
            <dt class="text-gray-500">Passengers</dt>
            <dd class="mt-0.5 text-lg font-bold text-gray-900"><?= $seatCount ?></dd>
        </div>
        <?php if ($vehicle) : ?>
            <div class="col-span-2">
                <dt class="text-gray-500">Vehicle</dt>
                <dd class="mt-0.5 font-semibold text-gray-900">
                    <?= htmlspecialchars($vehicle) ?>
                    <?php if (!empty($trip->vehicle_type)) : ?>
                        <span class="font-normal text-gray-500">(<?= htmlspecialchars($trip->vehicle_type) ?>)</span>
                    <?php endif; ?>
                </dd>
            </div>
        <?php endif; ?>
        <div class="col-span-2">
            <dt class="text-gray-500">Seats</dt>
            <dd class="mt-1.5 flex flex-wrap gap-1.5">
                <?php foreach ($seats as $seat) : ?>
                    <span class="grid h-9 min-w-[2.25rem] place-items-center rounded-lg bg-blue-600 px-2 font-bold text-white">
                        <?= htmlspecialchars($seat->seat_number) ?>
                    </span>
                <?php endforeach; ?>
            </dd>
        </div>
    </dl>
</div>