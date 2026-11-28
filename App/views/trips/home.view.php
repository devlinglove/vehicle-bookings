<?php

use Framework\Session;

/**
 * @var object|false $terminal
 * @var array $trips upcoming trips with stand, route, vehicle and seat counts
 */

 //$pageScripts = ['trips-show.js'];

//  echo phpinfo();

?>

<?php loadPartialView('header'); ?>
<?php loadPartialView('navbar'); ?>

<section class="container mx-auto px-4 py-8">

    <?php if (Session::has('admin')) : ?>
        <a href="/trips/create"
            class="inline-block bg-green-500 hover:bg-green-600 text-white font-semibold px-4 py-2 rounded-lg transition duration-200 mb-6">
            Create Trip
        </a>
    <?php endif; ?>

    <?php $tripCount = count($trips); ?>
    <h2 class="text-3xl font-bold mb-4">
        <?= $terminal ? htmlspecialchars($terminal->terminal_name) . ' - ' : '' ?><?= $tripCount ?> Upcoming <?= $tripCount === 1 ? 'Trip' : 'Trips' ?>
    </h2>

    <?php if (empty($trips)) : ?>
        <p class="text-gray-500 mt-8">No upcoming trips right now. Check back later.</p>
    <?php else : ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 items-start trips-list">

            <?php foreach ($trips as $trip) : ?>
                <?php
                $standActive = $trip->stand_status === 'active';
                $total       = (int) $trip->total_seats;
                $available   = $standActive ? (int) $trip->available_seats : 0;
                $canBook     = $standActive && $available > 0;

                // Bar shows how full the vehicle is
                $bookedPercent = ($standActive && $total > 0) ? round((($total - $available) / $total) * 100) : 0;

                // Bar colour by how many seats are left
                $leftPercent = $total > 0 ? ($available / $total) * 100 : 0;
                if (!$standActive) {
                    $barColor = 'bg-gray-300';
                } elseif ($leftPercent <= 25) {
                    $barColor = 'bg-red-500';      // almost full
                } elseif ($leftPercent <= 50) {
                    $barColor = 'bg-blue-600';     // filling up
                } else {
                    $barColor = 'bg-yellow-400';   // plenty of seats
                }

                // e.g. "Luxury Express #LEA-1234 (Business Class)"
                $vehicleLabel = ($trip->vehicle_name ?: 'Vehicle') . ' #' . $trip->vehicle_number;
                if ($trip->vehicle_type) {
                    $vehicleLabel .= ' (' . $trip->vehicle_type . ')';
                }
                ?>

                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">

                    <!-- Stand name + status badge -->
                    <div class="flex justify-between items-start gap-3">
                        <h3 class="text-2xl font-bold leading-tight">
                            <?= htmlspecialchars($trip->stand_name) ?>
                        </h3>
                        <?php if ($standActive) : ?>
                            <span class="shrink-0 bg-green-700 text-white text-sm font-semibold px-2 py-0.5 rounded">Active</span>
                        <?php else : ?>
                            <span class="shrink-0 bg-gray-500 text-white text-sm font-semibold px-2 py-0.5 rounded">
                                <?= htmlspecialchars(ucfirst($trip->stand_status)) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Route -->
                    <p class="text-gray-600 mt-2">
                        Route: <?= htmlspecialchars($trip->route_name) ?>
                    </p>

                    <hr class="my-4">

                    <!-- Vehicle -->
                    <p class="font-bold"><?= htmlspecialchars($vehicleLabel) ?></p>

                    <!-- Seat progress bar -->
                    <div class="w-full bg-gray-200 rounded-full h-2.5 mt-2"
                        role="progressbar"
                        aria-valuenow="<?= $bookedPercent ?>" aria-valuemin="0" aria-valuemax="100"
                        aria-label="<?= $bookedPercent ?>% of seats booked">
                        <div class="<?= $barColor ?> h-2.5 rounded-full" style="width: <?= $bookedPercent ?>%"></div>
                    </div>

                    <!-- Seats and fare -->
                    <p class="text-gray-600 mt-2">
                        <?php if ($standActive) : ?>
                            <?= $available ?> / <?= $total ?> Seats Available
                        <?php else : ?>
                            0 Seats Available
                        <?php endif; ?>
                        | Fare: Rs. <?= number_format((float) $trip->fare) ?>
                    </p>

                    <p class="text-gray-500 text-sm mt-1">
                        Departs <?= date('d M, h:i A', strtotime($trip->departure_time)) ?>
                    </p>

                    <!-- Button -->
                    <?php if ($canBook) : ?>
                        <a href="/trip?id=<?= (int) $trip->trip_id ?>"
                            class="block text-center w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded mt-4 transition">
                            Book Seat Online
                        </a>
                    <?php elseif ($standActive) : ?>
                        <button disabled class="w-full bg-gray-400 text-white px-4 py-2 rounded mt-4 cursor-not-allowed">
                            Fully Booked
                        </button>
                    <?php else : ?>
                        <button disabled class="w-full bg-gray-400 text-white px-4 py-2 rounded mt-4 cursor-not-allowed">
                            Unavailable
                        </button>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>

        </div>
    <?php endif; ?>

</section>

<?php loadPartialView('footer', [ 'pageScripts' => ['trips-show.js']]); ?>