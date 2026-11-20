<?php

/**
 * @var array $routes
 * @var array $stands
 * @var array $vehicles
 * @var array $errors
 * @var array $trip
 */

// Small helpers for this view
$old = fn($field) => htmlspecialchars($trip[$field] ?? '');
$selected = fn($field, $id) => (string) ($trip[$field] ?? '') === (string) $id ? 'selected' : '';
$inputClass = 'w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500';

?>

<?php loadPartialView('header'); ?>
<?php loadPartialView('navbar'); ?>

<section class="flex justify-center items-center mt-20 mb-10">

    <div class="bg-white p-8 rounded-lg shadow-md w-full md:w-[600px] mx-6">

        <h2 class="text-4xl text-center font-bold mb-6">
            Create Trip
        </h2>

        <form method="POST" action="/admin/trips/create">

            <!-- Route -->
            <div class="mb-4">
                <label for="route_id" class="block mb-2 font-semibold">Route</label>
                <select id="route_id" name="route_id" required class="<?= $inputClass ?>">
                    <option value="">Select a route</option>
                    <?php foreach ($routes as $route): ?>
                        <option value="<?= $route->route_id ?>" <?= $selected('route_id', $route->route_id) ?>>
                            <?= htmlspecialchars($route->route_name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <!-- <?php if (!empty($errors['route_id'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($errors['route_id']) ?></p>
                <?php endif; ?> -->
            </div>

            <!-- Stand (grouped by terminal) -->
             <div class="mb-4">
                <label for="stand_id" class="block mb-2 font-semibold">Stand</label>
                <select id="stand_id" name="stand_id" required class="<?= $inputClass ?>">
                    <option value="">Select a stand</option>
                    <?php foreach ($stands as $stand): ?>
                        <option value="<?= $stand->stand_id ?>" <?= $selected('route_id', $stand->stand_id) ?>>
                            <?= htmlspecialchars($stand->stand_name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <!-- <?php if (!empty($errors['stand_id'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($errors['stand_id']) ?></p>
                <?php endif; ?> -->
            </div>
            

            <!-- Vehicle -->
            <div class="mb-4">
                <label for="vehicle_id" class="block mb-2 font-semibold">Vehicle</label>
                <select id="vehicle_id" name="vehicle_id" required class="<?= $inputClass ?>">
                    <option value="">Select a vehicle</option>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <option value="<?= $vehicle->vehicle_id ?>" <?= $selected('vehicle_id', $vehicle->vehicle_id) ?>>
                            <?= htmlspecialchars($vehicle->vehicle_number) ?> (<?= (int) $vehicle->total_seats ?> seats)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['vehicle_id'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($errors['vehicle_id']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Departure time -->
            <div class="mb-4">
                <label for="departure_time" class="block mb-2 font-semibold">Departure date and time</label>
                <input
                    type="datetime-local"
                    id="departure_time"
                    name="departure_time"
                    value="<?= $old('departure_time') ?>"
                    required
                    class="<?= $inputClass ?>" />
                <?php if (!empty($errors['departure_time'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($errors['departure_time']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Fare and seats side by side -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="fare" class="block mb-2 font-semibold">Fare (Rs)</label>
                    <input
                        type="number"
                        id="fare"
                        name="fare"
                        min="1"
                        step="0.01"
                        placeholder="e.g. 1500"
                        value="<?= $old('fare') ?>"
                        required
                        class="<?= $inputClass ?>" />
                    <?php if (!empty($errors['fare'])): ?>
                        <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($errors['fare']) ?></p>
                    <?php endif; ?>
                </div>

                
            </div>

            <!-- Buttons -->
            <button
                type="submit"
                class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold px-4 py-2 my-3 rounded transition">
                Create Trip
            </button>

            <a
                href="/admin/trips"
                class="block text-center w-full bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded transition">
                Cancel
            </a>

        </form>

    </div>

</section>

<?php loadPartialView('footer'); ?>