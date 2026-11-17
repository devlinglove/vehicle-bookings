<?php

use Framework\Session;
?>


<?php

/**
 * @var array  $listings
 */

?>

<?php loadPartialView('header'); ?>
<?php loadPartialView('navbar'); ?>

<!-- Job Listings -->
<section>
  <div class="container mx-auto p-4 mt-4">
    <div class="text-center text-3xl mb-4 font-bold border border-gray-300 p-3">Transporters</div>
    <?php if (Session::has('admin')) : ?>
      <a href="/transporters/create"
        class="inline-block bg-green-500 hover:bg-green-600 text-white font-semibold px-4 py-2 rounded-lg transition duration-200">
        Add Transport
      </a>
      <a href="/transporters/update"
        class="inline-block bg-green-500 hover:bg-green-600 text-white font-semibold px-4 py-2 rounded-lg transition duration-200">
        Update Transport
      </a>

      <a href="/transporters/delete"
        class="inline-block bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded transition duration-200">
        Delete Transport
      </a>

    <?php endif; ?>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    <?php foreach ($listings as $listing): ?>

      <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 mb-4">

        <div class="flex items-center gap-4">

          <!-- Transport Picture -->
          <img
            src="<?= htmlspecialchars($listing->transport_picture) ?>"
            alt="Transport"
            class="w-16 h-16 object-cover rounded-lg border">

          <!-- Transport Information -->
          <div>
            <h2 class="text-lg font-bold text-gray-800">
              <?= htmlspecialchars($listing->transport_name) ?>
            </h2>

            <div class="flex flex-wrap gap-4 mt-2 text-sm text-gray-500">

              <span>
                ID:
                <strong class="text-gray-700">
                  <?= (int) $listing->id ?>
                </strong>
              </span>

              <span>
                Transporter ID:
                <strong class="text-gray-700">
                  <?= (int) $listing->transport_id ?>
                </strong>
              </span>

            </div>

            <!-- Transporter Link -->
            <!-- <a
              href="/transporters/<?= (int) $listing->transport_id ?>"
              class="inline-block mt-2 text-blue-600 hover:text-blue-800 text-sm font-medium">
              View Transporter →
            </a> -->

          </div>

        </div>

      </div>

    <?php endforeach; ?>


  </div>


</section>


<?php loadPartialView('footer'); ?>