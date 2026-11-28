<?php

use Framework\Session;

/**
 * GET /checkout?booking={id} — everything comes from the database, never the browser.
 *
 * @var object $booking      booking_id, amount, status
 * @var int    $secondsLeft  seconds until the hold expires (calculated on the server)
 * @var object $trip         trip_id, route_name, stand_name, departure_time, vehicle_*, fare
 * @var array  $seats        selected seats: trip_seat_id, seat_number, (optional) gender
 * @var array  $errors       e.g. ['phone' => 'Enter a valid mobile number']
 * @var array  $old          submitted values after a failed submit
 */

$errors = $errors ?? [];
$old    = $old ?? [];

$val = fn(string $key) => htmlspecialchars($old[$key] ?? '');
$err = fn(string $key) => isset($errors[$key])
    ? '<p class="mt-1 text-sm text-red-600">' . htmlspecialchars($errors[$key]) . '</p>'
    : '';
$input = fn(string $key) => 'w-full rounded-lg border bg-white px-3 py-2.5 text-gray-900 placeholder:text-gray-400 '
    . 'focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 '
    . (isset($errors[$key]) ? 'border-red-400' : 'border-gray-300');

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

$backUrl = '/trip?id=' . (int) $trip->trip_id;

?>

<?php loadPartialView('header'); ?>
<?php loadPartialView('navbar'); ?>

<style>
    /* Ticket: the shadow follows the cut-out notches */
    .ticket {
        filter: drop-shadow(0 1px 1px rgb(15 23 42 / .06)) drop-shadow(0 12px 28px rgb(15 23 42 / .10));
    }

    /* Tear line with half-circle notches on both edges (transparent, so any page background shows through) */
    .ticket-perf {
        height: 28px;
        background:
            radial-gradient(circle at 0 50%, transparent 13px, #fff 13.5px) left / 50.5% 100% no-repeat,
            radial-gradient(circle at 100% 50%, transparent 13px, #fff 13.5px) right / 50.5% 100% no-repeat;
    }
</style>

<section class="container mx-auto px-4 py-8">

    <!-- Steps -->
    <ol class="flex flex-wrap items-center gap-x-3 gap-y-2 text-sm" aria-label="Booking progress">
        <li>
            <a href="<?= $backUrl ?>" class="flex items-center gap-2 font-semibold text-blue-600 hover:underline">
                <span class="grid h-6 w-6 place-items-center rounded-full bg-blue-600 text-white">
                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor" aria-hidden="true">
                        <path d="M7.6 13.2 4.4 10l-1.1 1.1 4.3 4.3 9-9-1.1-1.1z" />
                    </svg>
                </span>
                Seats
            </a>
        </li>
        <li class="h-px w-6 bg-gray-300" aria-hidden="true"></li>
        <li class="flex items-center gap-2 font-semibold text-gray-900" aria-current="step">
            <span class="grid h-6 w-6 place-items-center rounded-full border-2 border-blue-600 text-xs text-blue-600">2</span>
            Details and payment
        </li>
        <li class="h-px w-6 bg-gray-300" aria-hidden="true"></li>
        <li class="flex items-center gap-2 text-gray-400">
            <span class="grid h-6 w-6 place-items-center rounded-full border-2 border-gray-300 text-xs">3</span>
            Ticket
        </li>
    </ol>

    <div class="mt-6 flex flex-wrap items-end justify-between gap-4">
        <h2 class="text-3xl font-bold">Almost there</h2>

        <!-- Hold timer (checkout.js counts down and turns it red in the last minute) -->
        <div id="hold-timer"
            data-seconds-left="<?= max(0, (int) $secondsLeft) ?>"
            class="flex items-center gap-2 rounded-full border border-yellow-300 bg-yellow-50 px-4 py-2 text-sm text-yellow-800">
            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="13" r="8" />
                <path d="M12 9v4l2.5 2.5M9 2h6" />
            </svg>
            Seats held for <span class="hold-countdown font-bold tabular-nums">--:--</span>
        </div>
    </div>

    <div id="hold-expired" class="hidden mt-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">
        Your hold ended and the seats were released.
        <a href="<?= $backUrl ?>" class="font-semibold underline">Choose seats again</a>
    </div>

    <?php if ($errors) : ?>
        <div class="mt-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">
            Some details need fixing. Check the fields marked in red.
        </div>
    <?php endif; ?>

    <form method="POST" action="/initiate-payment" id="checkout-form" novalidate
        class="mt-6 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_24rem]">

        <input type="hidden" name="booking_id" value="<?= (int) $booking->booking_id ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::get('csrf_token') ?? '') ?>">

        <div class="flex flex-col gap-6">

            <!-- ============ Contact ============ -->
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 sm:p-6">
                <h3 class="text-xl font-bold">Where to send your ticket</h3>
                <p class="mt-1 text-sm text-gray-500">We'll also text you if the departure time changes.</p>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="phone" class="mb-1 block text-sm font-semibold text-gray-700">Mobile number</label>
                        <input id="phone" name="phone" type="tel" required inputmode="numeric"
                            pattern="03[0-9]{9}" maxlength="11" placeholder="03XXXXXXXXX" autocomplete="tel"
                            value="<?= $val('phone') ?>" class="<?= $input('phone') ?>">
                        <?= $err('phone') ?>
                    </div>
                    <div>
                        <label for="cnic" class="mb-1 block text-sm font-semibold text-gray-700">CNIC</label>
                        <input id="cnic" name="cnic" type="text" required inputmode="numeric"
                            pattern="[0-9]{5}-?[0-9]{7}-?[0-9]" maxlength="15" placeholder="12345-1234567-1"
                            value="<?= $val('cnic') ?>" class="<?= $input('cnic') ?>">
                        <?= $err('cnic') ?>
                    </div>
                    <div>
                        <label for="name" class="mb-1 block text-sm font-semibold text-gray-700">
                            Name
                            <span class="font-normal text-gray-400"></span>
                        </label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            maxlength="150"
                            value="<?= $val('email') ?>"
                            class="<?= $input('name') ?>" />
                        <?= $err('name') ?>
                    </div>
                    <div>
                        <label for="email" class="mb-1 block text-sm font-semibold text-gray-700">
                            Email <span class="font-normal text-gray-400"></span>
                        </label>
                        <input id="email" name="email" type="email" maxlength="150" autocomplete="email"
                            value="<?= $val('email') ?>" class="<?= $input('email') ?>">
                        <?= $err('email') ?>
                    </div>


                </div>
            </div>

            <!-- ============ Payment ============ -->
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 sm:p-6">
                <h3 class="text-xl font-bold">Payment</h3>

                <label class="mt-5 flex cursor-pointer items-start gap-4 rounded-xl border-2 border-green-600 bg-green-50 p-4">
                    <input type="radio" name="payment_method" value="easypaisa_ma" checked class="mt-1 h-4 w-4 accent-green-600">
                    <span class="flex-1">
                        <span class="flex items-center gap-2 font-semibold text-gray-900">
                            <span class="grid h-7 w-7 place-items-center rounded-md bg-green-600 text-xs font-bold text-white">ep</span>
                            Easypaisa account
                        </span>
                        <span class="mt-1 block text-sm text-gray-600">
                            After you tap Pay, open the Easypaisa app and approve the request.
                        </span>
                    </span>
                </label>

                <div class="mt-4">
                    <label for="easypaisa_number" class="mb-1 block text-sm font-semibold text-gray-700">Easypaisa account number</label>
                    <input id="easypaisa_number" name="easypaisa_number" type="tel" required inputmode="numeric"
                        pattern="03[0-9]{9}" maxlength="11" placeholder="03XXXXXXXXX"
                        value="<?= $val('easypaisa_number') ?>" class="<?= $input('easypaisa_number') ?> read-only:bg-gray-50">
                    <label class="mt-2 flex w-fit cursor-pointer items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" id="same-as-phone" class="h-4 w-4 accent-blue-600">
                        Same as my mobile number
                    </label>
                    <?= $err('easypaisa_number') ?>
                </div>
            </div>
        </div>

        <!-- ============ Ticket summary ============ -->
        <aside class="ticket lg:sticky lg:top-8">

            <!-- Top half -->
            <!-- <div class="rounded-t-2xl bg-white p-6">
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
            </div> -->

            <?php loadPartialView('trip-detail', [
                'booking' => $booking,
                'seats' => $seats,
                'trip' => $trip
            ]); ?>


            <!-- Tear line -->
            <div class="ticket-perf relative" aria-hidden="true">
                <div class="absolute inset-x-6 top-1/2 border-t-2 border-dashed border-gray-200"></div>
            </div>

            <!-- Stub -->
            <div class="rounded-b-2xl bg-white p-6 pt-3">
                <!-- <div class="space-y-2 text-sm text-gray-600">
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
                </div> -->

                <?php loadPartialView('trip-seats', [
                    'booking' => $booking,
                    'seats' => $seats,
                    'trip' => $trip
                ]); ?>


                <button type="submit" id="pay-button"
                    class="mt-5 w-full rounded-lg bg-blue-600 px-4 py-3 font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus-visible:ring-4 focus-visible:ring-blue-200 disabled:cursor-not-allowed disabled:bg-gray-400">
                    Pay Rs. <?= number_format($amount) ?> with Easypaisa
                </button>

                <!-- <a href="/selection?trip_id=<?= (int) $trip->trip_id ?>&booking_id=<?= (int) $booking->booking_id ?>"
                    class="block text-center w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded mt-4 transition">
                    Go to selection
                </a> -->

                <p class="mt-3 flex items-center justify-center gap-1.5 text-xs text-gray-500">
                    <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="5" y="11" width="14" height="10" rx="2" />
                        <path d="M8 11V7a4 4 0 0 1 8 0v4" />
                    </svg>
                    Payment is processed by Easypaisa.
                </p>
                <p class="mt-1 text-center text-xs text-gray-500">
                    By paying you agree to the
                    <a href="#" class="font-semibold text-blue-600 hover:underline">terms and conditions</a>.
                </p>
            </div>
        </aside>
    </form>
</section>

<?php loadPartialView('footer', [
    'pageScripts' => ['checkout.js']
]); ?>