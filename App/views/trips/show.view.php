<?php

/**
 * @var object $seats
 * @var object $trip_id
 */

 $pageScripts = ['trip-show.js'];

?>



<?php loadPartialView('header'); ?>
<?php loadPartialView('navbar'); ?>
<?php loadPartialView('top-banner'); ?>


<main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:py-12">

    <!-- Header -->
    <header class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-navy-900 sm:text-3xl">Choose your seats</h1>
            <p class="mt-1 text-sm text-slate-500">Lahore to Islamabad, Sat 3 Oct, 10:30 AM, Executive</p>
        </div>
        <p class="rounded-full bg-white px-4 py-2 text-sm text-slate-600 ring-1 ring-slate-200">
            You can book up to <span class="font-bold text-navy-700">4 seats</span> at a time
        </p>
    </header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,26rem)_1fr]">

        <!-- ============ LEFT COLUMN: Seat map ============ -->
        <section class="rounded-3xl bg-white p-5 shadow-[0_1px_2px_rgba(6,41,77,.06),0_12px_32px_-12px_rgba(6,41,77,.18)] ring-1 ring-slate-100 sm:p-7">
            <div class="mb-5 flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-slate-500">Available seats</p>
                    <p class="text-2xl font-extrabold text-navy-700">
                        <span class="available-seats">5</span>/<span class="text-slate-400 total-seats">41</span>
                    </p>
                </div>

                <!-- Gender toggle (pure CSS radios) -->
                <fieldset class="flex rounded-xl bg-slate-100 p-1 text-sm font-semibold">
                    <legend class="sr-only">Booking for</legend>
                    <label class="cursor-pointer">
                        <input type="radio" name="gender" value="male" class="peer sr-only" checked />
                        <span class="block rounded-lg px-3 py-1.5 text-slate-500 transition peer-checked:bg-navy-600 peer-checked:text-white peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-navy-600">Male</span>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="gender" value="female" class="peer sr-only" />
                        <span class="block rounded-lg px-3 py-1.5 text-slate-500 transition peer-checked:bg-rose-400 peer-checked:text-white peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-rose-400">Female</span>
                    </label>
                </fieldset>
            </div>

            <!-- Bus body -->
            <div class="rounded-[2rem] border-2 border-slate-200 bg-slate-50/60 p-4 sm:p-5">
                <div class="mb-4 flex items-center justify-between border-b-2 border-dashed border-slate-200 pb-3">
                    <span class="text-xs font-semibold text-slate-400">Front</span>
                    <span class="grid h-9 w-9 place-items-center rounded-full border-2 border-slate-300 text-slate-400" title="Driver">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="9" />
                            <circle cx="12" cy="12" r="2" />
                            <path d="M12 14v7M10 12H3M14 12h7" />
                        </svg>
                    </span>
                </div>

                <div class="relative seat-grid flex flex-wrap gap-2">
                    
                </div>

                <div class="mt-4 border-t-2 border-dashed border-slate-200 pt-3 text-center text-xs font-semibold text-slate-400">Back</div>
            </div>
        </section>

        <!-- ============ RIGHT COLUMN: Legend + summary ============ -->
        <aside class="flex flex-col gap-6 lg:sticky lg:top-8 lg:self-start">

            <div class="rounded-3xl bg-white p-6 ring-1 ring-slate-100">
                <h2 class="mb-4 text-base font-bold text-navy-900">Seat guide</h2>
                <ul class="grid grid-cols-1 gap-3 text-sm text-slate-600 sm:grid-cols-2 sm:gap-x-6">
                    <li class="flex items-center gap-3"><span class="h-6 w-6 shrink-0 rounded-md bg-navy-600"></span>Reserved</li>
                    <li class="flex items-center gap-3">
                        <span class="grid h-6 w-6 shrink-0 place-items-center rounded-md border-2 border-navy-600 bg-navy-50 text-navy-600">
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path d="M7.6 13.2 4.4 10l-1.1 1.1 4.3 4.3 9-9-1.1-1.1z" />
                            </svg>
                        </span>
                        Selected
                    </li>
                    <li class="flex items-center gap-3"><span class="h-6 w-6 shrink-0 rounded-md border-2 border-slate-200 bg-white"></span>Available</li>
                </ul>
            </div>

            <div class="rounded-3xl bg-white p-6 ring-1 ring-slate-100">
                <div class="mb-4 flex items-baseline justify-between">
                    <h2 class="text-base font-bold text-navy-900">Your seats</h2>
                    <span class="text-sm font-semibold text-slate-400">1 of 4</span>
                </div>

                <!-- Selected seat chips -->
                <div class="flex min-h-[3.5rem] flex-wrap content-start gap-2 chip-container">
                
                </div>

                <dl class="mt-5 space-y-2 border-t border-slate-100 pt-5 text-sm">
                    <!-- <div class="flex justify-between text-slate-500">
                        <dt>Fare per seat</dt>
                        <dd>Rs 2,500</dd>
                    </div> -->
                    <div class="flex justify-between text-base font-bold text-navy-900 total-container hidden">
                        <dt>Total</dt>
                        <dd class="total-price">Rs 2,500</dd>
                    </div>
                </dl>

                <button 
                    type="button"
                    class="payment-button mt-5 w-full rounded-2xl bg-navy-600 px-5 py-3.5 text-sm font-bold text-white transition hover:bg-navy-700 focus:outline-none focus-visible:ring-4 focus-visible:ring-navy-100 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400"
                >
                    Continue to passenger details
                </button>

                <!-- Show when 4 seats are selected -->
                <p class="mt-3 hidden text-center text-xs font-semibold text-rose-500">You've reached the 4-seat limit. Remove a seat to pick another.</p>
            </div>

            <p class="flex flex-wrap items-center gap-x-2 gap-y-1 px-2 text-sm text-slate-500">
                <svg viewBox="0 0 24 24" class="h-5 w-5 text-navy-600" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z" />
                </svg>
                For refunds and cancellations, review the
                <a href="#" class="font-bold text-navy-600 underline-offset-4 hover:underline">terms and conditions</a>
            </p>
        </aside>
    </div>
</main>


<script>
    window.tripId = <?= (int) $trip_id ?>;
</script>
<?php loadPartialView('footer', [
    'pageScripts' => ['trip-show.js']
]); ?>