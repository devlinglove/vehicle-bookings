 <?php 
 
 /**
 * @var array  $trips
 */

 ?>

<?php loadPartialView('header'); ?>
<?php loadPartialView('navbar'); ?>
<?php loadPartialView('top-banner'); ?>

<!-- Trips -->
<section>
  <div class="container mx-auto p-4 mt-4">
    <div class="text-center text-3xl mb-4 font-bold border border-gray-300 p-3">Trips</div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <!-- Job Listing -->
      <?php foreach ($trips as $trip) : ?>
        <div class="rounded-lg shadow-md bg-white">
          <div class="p-4">
            <h2 class="text-xl font-semibold"><?= $trip->origin; ?> - <?= $trip->destination; ?></h2>
            
            <a href="/trip?id=<?= $trip->id ?>" class="block w-full text-center px-5 py-2.5 shadow-sm rounded border text-base font-medium text-indigo-700 bg-indigo-100 hover:bg-indigo-200">
              View Seats
            </a>
          </div>
        </div>

      <?php endforeach; ?>

</section>


<?php loadPartialView('bottom-banner'); ?>
<?php loadPartialView('footer'); ?>