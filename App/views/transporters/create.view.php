<?php loadPartialView('header'); ?>

<section class="flex justify-center items-center mt-20 mb-10">

    <div class="bg-white p-8 rounded-lg shadow-md w-full md:w-[600px] mx-6">

        <h2 class="text-4xl text-center font-bold mb-6">
            Add Transport
        </h2>

        <form method="POST" action="/transporters/create">

            <h2 class="text-2xl font-bold mb-6 text-center text-gray-500">
                Transport Info
            </h2>

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
            </div>

            <!-- Transport Name -->
            <div class="mb-4">
                <label class="block mb-2 font-semibold">
                    Transport Name
                </label>

                <input
                    type="text"
                    name="transporter_name"
                    placeholder="e.g. Coaster"
                    required
                    class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500" />
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
                    required
                    class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500" />
            </div>

            <!-- Buttons -->
            <button
                type="submit"
                class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold px-4 py-2 my-3 rounded transition">
                Add Transport
            </button>

            <a
                href="/transporters"
                class="block text-center w-full bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded transition">
                Cancel
            </a>

        </form>

    </div>

</section>

<?php loadPartialView('footer'); ?>