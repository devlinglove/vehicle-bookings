<?php

/**
 * @var array $errors
 * @var object|null $transporter
 */

$errors = $errors ?? [];
$transporter = $transporter ?? null;

?>


<?php loadPartialView('header'); ?>

<section class="flex justify-center items-center mt-20 mb-10">

    <div class="bg-white p-8 rounded-lg shadow-md w-full md:w-[600px] mx-6">

        <h2 class="text-4xl text-center font-bold mb-6">
            Delete Transport
        </h2>

        <form method="POST" action="/transporters/search-delete">
            <div class="mb-4">
                <label class="block mb-2 font-semibold">
                    Transporter ID
                </label>

                <input
                    type="number"
                    name="transporter_id"
                    placeholder="Enter Transporter ID"
                    min="1"
                    step="1"
                    required
                    class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500" />
                <button
                    type="submit"
                    class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold px-4 py-2 my-3 rounded transition">
                    Search
                </button>
            </div>
        </form>
        <?php if (!empty($transporter)): ?>

            <form method="POST" action="/transporters/delete">

                <h2 class="text-2xl font-bold mb-6 text-center text-gray-500">
                    Transport Info
                </h2>

                <input type="hidden" name="id"
                    value="<?= htmlspecialchars((string) $transporter->id) ?>">

                <!-- Transport Name -->
                <div class="mb-4">
                    <label class="block mb-2 font-semibold">
                        Transport Name
                    </label>

                    <input
                        type="text"
                        name="transporter_name"
                        value="<?= htmlspecialchars($transporter->transport_name) ?>"
                        readonly
                        class="w-full px-4 py-2 border rounded" />
                </div>

                <!-- Transport Picture URL -->
                <div class="mb-4">
                    <label class="block mb-2 font-semibold">
                        Transport Picture URL
                    </label>

                    <input
                        type="url"
                        name="transporter_picture"
                        placeholder="https://example.com/coaster.jpg"
                        value="<?= htmlspecialchars($transporter->transport_picture) ?>"
                        readonly
                        class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500" />
                </div>

                <!-- Buttons -->
                <button
                    type="submit"
                    class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold px-4 py-2 my-3 rounded transition">
                    Delete Transport
                </button>

                <a
                    href="/transporters"
                    class="block text-center w-full bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded transition">
                    Cancel
                </a>

            </form>

        <?php endif; ?>

        <?php if (isset($errors['transporter_id'])): ?>

            <div class="mt-3 p-3 bg-red-100 text-red-700 rounded">
                <?= htmlspecialchars($errors['transporter_id']) ?>
            </div>

        <?php endif; ?>

    </div>

</section>

<?php loadPartialView('footer'); ?>