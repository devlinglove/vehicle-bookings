<?php

/**
 * @var object $booking      booking_id, amount, status
 * @var object $trip         trip_id, route_name, stand_name, departure_time, vehicle_*, fare
 * @var array  $seats        selected seats: trip_seat_id, seat_number, (optional) gender
 */

$fare      = (float) $trip->fare;
$amount    = (float) $booking->amount;
$seatCount = count($seats);


?>

<?php loadPartialView('header'); ?>
<?php loadPartialView('navbar'); ?>


<section class="container mx-auto px-4 py-8 max-w-7xl grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Payment Section -->
    <div class="lg:col-span-2 bg-white rounded-2xl border p-6">
        <h2 class="text-2xl font-bold">Payment</h2>

        <p class="text-gray-500 mb-6">
            Choose how you'd like to pay for your seats.
        </p>

        <!-- Payment methods -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            
            <!-- Online payment card -->
            <div id="online-card"
                data-method="online"
                class="payment-card cursor-pointer rounded-2xl border-2 border-gray-200 p-6">

                <input type="radio" name="payment_method"
                    value="online" class="hidden">

                <h3 class="font-semibold text-lg">Pay online now</h3>

                <p class="text-gray-500 mt-2">
                    Card or wallet through Safepay.
                    Your seats are confirmed straight away.
                </p>
            </div>


            <!-- Voucher payment card -->
            <div id="voucher-card"
                data-method="voucher"
                class="payment-card cursor-pointer rounded-2xl border-2 border-blue-600 bg-blue-50 p-6">

                <input type="radio" name="payment_method"
                    value="voucher" checked class="hidden">

                <h3 class="font-semibold text-lg">Pay by voucher</h3>

                <p class="text-gray-500 mt-2">
                    Pay at a bank or in your banking app,
                    then upload the receipt.
                </p>
            </div>

        </div>

    </div>



    <aside class="ticket lg:sticky lg:top-8">
        <?php loadPartialView('trip-detail', [
            'booking' => $booking,
            'seats' => $seats,
            'trip' => $trip
        ]); ?>

        <!-- Tear line -->
        <div class="ticket-perf relative" aria-hidden="true">
            <div class="absolute inset-x-6 top-1/2 border-t-2 border-dashed border-gray-200"></div>
        </div>


        <div class="rounded-b-2xl bg-white p-6 pt-3">

            <?php loadPartialView('trip-seats', [
                'booking' => $booking,
                'seats' => $seats,
                'trip' => $trip
            ]); ?>

            <form action="/payment-redirect" method="POST">

                <input type="hidden" name="payment_method" class="payment_method" value="voucher">
                <input type="hidden" name="booking_id" value="<?= $booking->booking_id ?>">

                <button type="submit" id="payment-action" class="mt-5 w-full rounded-lg bg-blue-600 px-4 py-3 font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus-visible:ring-4 focus-visible:ring-blue-200 disabled:cursor-not-allowed disabled:bg-gray-400">
                    Pay
                </button>

            </form>


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

    </div>

    <script>
        const cards = document.querySelectorAll('.payment-card');
        const actionButton = document.getElementById('payment-action');

        let selectedMethod = 'voucher';

        function setBtnText(selectedMethod) {
            actionButton.textContent = selectedMethod === 'online' ? 'Pay online now' : 'Generate voucher';
        }

        cards.forEach(card => {
            card.addEventListener('click', () => {

                cards.forEach(item => {
                    item.classList.remove(
                        'border-blue-600',
                        'bg-blue-50'
                    );

                    item.classList.add('border-gray-200');
                });

                card.classList.remove('border-gray-200');
                card.classList.add('border-blue-600', 'bg-blue-50');

                selectedMethod = card.dataset.method;

                document.querySelector('.payment_method').value = card.dataset.method

                setBtnText(selectedMethod)

            });
        });

        setBtnText(selectedMethod)
    </script>


    <?php loadPartialView('footer'); ?>