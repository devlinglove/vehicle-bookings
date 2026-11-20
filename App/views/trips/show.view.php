<?php

/**
 * @var array  $seats
 * @var int    $trip_id
 * @var object $trip
 */

use Framework\Session;

$total       = (int) $trip->total_seats;
$available   = (int) $trip->available_seats;
$bookedPct   = $total > 0 ? round((($total - $available) / $total) * 100) : 0;
$leftPct     = $total > 0 ? ($available / $total) * 100 : 0;

// Same bar colours as the trips list
if ($leftPct <= 25) {
  $barColor = 'bg-red-500';
} elseif ($leftPct <= 50) {
  $barColor = 'bg-blue-600';
} else {
  $barColor = 'bg-yellow-400';
}

$vehicleLabel = '';
if (!empty($trip->vehicle_number)) {
  $vehicleLabel = (($trip->vehicle_name ?? '') ?: 'Vehicle') . ' #' . $trip->vehicle_number;
  if (!empty($trip->vehicle_type)) {
    $vehicleLabel .= ' (' . $trip->vehicle_type . ')';
  }
}

$fare = isset($trip->fare) ? (float) $trip->fare : null;

if (!Session::has('hold_token')) {
  Session::set('hold_token', bin2hex(random_bytes(32)));
}

?>

<?php loadPartialView('header'); ?>
<?php loadPartialView('navbar'); ?>

<section class="container mx-auto px-4 py-8" data-fare="<?= $fare ?? '' ?>">

  <!-- Trip summary -->
  <a href="/trips" class="text-sm font-semibold text-blue-600 hover:underline">Back to trips</a>

  <h2 class="text-3xl font-bold mt-2">Choose your seats</h2>

  <p class="text-gray-600 mt-2">
    <?php if (!empty($trip->stand_name)) : ?>
      <?= htmlspecialchars($trip->stand_name) ?> |
    <?php endif; ?>
    Route: <?= htmlspecialchars($trip->route_name) ?>
    <?php if ($fare !== null) : ?>
      | Fare: Rs. <?= number_format($fare) ?> per seat
    <?php endif; ?>
  </p>

  <p class="text-gray-500 text-sm mt-1">
    Departs <?= date('d M, h:i A', strtotime($trip->departure_time)) ?>
    <?php if ($vehicleLabel) : ?>
      on <span class="font-semibold text-gray-700"><?= htmlspecialchars($vehicleLabel) ?></span>
    <?php endif; ?>
  </p>

  <div class="grid gap-6 mt-6 lg:grid-cols-[minmax(0,1fr)_22rem] items-start">

    <!-- ============ Seat map ============ -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 sm:p-5">

      <div class="flex flex-wrap justify-between items-start gap-4">
        <div class="min-w-[12rem] flex-1">
          <p class="text-gray-600">
            <span class="available-seats font-bold text-gray-900"><?= $available ?></span>
            / <span class="total-seats"><?= $total ?></span> Seats Available
          </p>

          <!-- Same progress bar as the trip card -->
          <div class="w-full max-w-xs bg-gray-200 rounded-full h-2.5 mt-2"
            role="progressbar"
            aria-valuenow="<?= $bookedPct ?>" aria-valuemin="0" aria-valuemax="100"
            aria-label="<?= $bookedPct ?>% of seats booked">
            <div class="seat-progress <?= $barColor ?> h-2.5 rounded-full" style="width: <?= $bookedPct ?>%"></div>
          </div>
        </div>

      </div>

      <hr class="my-4">

      <!-- Bus outline -->
      <div class="mx-auto max-w-xs rounded-3xl border-2 border-gray-200 bg-gray-50 p-3 sm:p-4">

        <div class="mb-4 flex items-center justify-between border-b-2 border-dashed border-gray-200 pb-3">
          <span class="text-xs font-semibold text-gray-400">Door</span>
          <span class="grid h-9 w-9 place-items-center rounded-full border-2 border-gray-300 text-gray-400" title="Driver">
            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <circle cx="12" cy="12" r="9" />
              <circle cx="12" cy="12" r="2" />
              <path d="M12 14v7M10 12H3M14 12h7" />
            </svg>
          </span>
        </div>

        <!--
                    2 + 2 layout with an aisle. Every 3rd seat in a row jumps to column 4,
                    so the aisle survives even if JS re-renders the buttons.
                -->
        <div class="seat-grid grid grid-cols-[repeat(2,minmax(0,1fr))_1.25rem_repeat(2,minmax(0,1fr))] gap-2 [&>*:nth-child(4n+3)]:col-start-4">
          <?php foreach ($seats as $seat) : ?>
            <?php
            $locked = in_array($seat->status, ['booked', 'reserved', 'hold'], true);
            $label  = 'Seat ' . $seat->seat_number . ', ' . ($locked ? ($seat->status === 'hold' ? 'on hold' : 'booked') : 'available');
            ?>
            <button
              type="button"
              class="<?= htmlspecialchars(seatClass($seat->status)) ?>"
              data-seat-id="<?= (int) $seat->trip_seat_id ?>"
              aria-label="<?= htmlspecialchars($label) ?>"
              <?= $locked ? 'disabled' : '' ?>>
              <?= htmlspecialchars($seat->seat_number) ?>
            </button>
          <?php endforeach; ?>
        </div>

        <div class="mt-4 border-t-2 border-dashed border-gray-200 pt-3 text-center text-xs font-semibold text-gray-400">Back</div>
      </div>
    </div>

    <!-- ============ Summary ============ -->
    <aside class="flex flex-col gap-6 lg:sticky lg:top-8">

      <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <div class="flex justify-between items-baseline">
          <h3 class="text-xl font-bold">Your seats</h3>
          <span class="text-sm text-gray-500"><span class="selected-count">0</span> of 4</span>
        </div>

        <!-- Selected seat chips (filled by trip-show.js) -->
        <div class="chip-container flex min-h-[2.5rem] flex-wrap content-start gap-2 mt-3"></div>

        <hr class="my-4">

        <div class="total-container hidden flex justify-between text-lg font-bold">
          <span>Total</span>
          <span class="total-price">Rs. 0</span>
        </div>

        <div id="seat-inputs"></div>
        <form method="POST" action="/checkout" id="continue-form">
          <input type="hidden" name="trip_id" value="<?= (int) $trip_id ?>">

          <button
            type="submit"
            class="payment-button w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded mt-4 transition disabled:bg-gray-400 disabled:cursor-not-allowed">
            Continue to passenger details
          </button>

        </form>


        <p class="seat-limit-msg hidden mt-3 text-center text-sm text-red-500">
          You can book up to 4 seats. Remove one to pick another.
        </p>
      </div>

      <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <h3 class="font-bold mb-3">Seat guide</h3>
        <ul class="grid grid-cols-2 gap-3 text-sm text-gray-600">
          <li class="flex items-center gap-2">
            <span class="h-5 w-5 shrink-0 rounded border-2 border-gray-300 bg-white"></span>Available
          </li>
          <li class="flex items-center gap-2">
            <span class="h-5 w-5 shrink-0 rounded border-2 border-blue-600 bg-blue-600"></span>Selected
          </li>
          <li class="flex items-center gap-2">
            <span class="h-5 w-5 shrink-0 rounded border-2 border-yellow-300 bg-yellow-100"></span>On hold
          </li>
          <li class="flex items-center gap-2">
            <span class="h-5 w-5 shrink-0 rounded border-2 border-gray-200 bg-gray-200"></span>Booked
          </li>
        </ul>
      </div>

      <p class="text-sm text-gray-500 px-1">
        For refunds and cancellations, see the
        <a href="#" class="font-semibold text-blue-600 hover:underline">terms and conditions</a>.
      </p>
    </aside>
  </div>
</section>

<?php if (!Session::has('csrf_token')) Session::set('csrf_token', bin2hex(random_bytes(32))); ?>
<script>
  window.tripId = <?= (int) $trip_id ?>;
  window.holdToken = <?= json_encode(Session::get('hold_token')) ?>;
  window.csrfToken = <?= json_encode(Session::get('csrf_token')) ?>;
</script>
<?php loadPartialView('footer', ['pageScripts' => ['trip-show.js']]); ?>