<!-- <script src="/assets/js/socket.js"></script> -->
<!-- <script src="/assets/js/trip-show.js"></script> -->

<?php

/**
 * @var object $pageScripts
 * @var object $trip_id
 */

// var_dump($pageScripts)

?>

</main>
<footer class="bg-blue-900 text-white p-2">
  <div class="container mx-auto flex justify-center items-center">
    © 2026 Online Application - Traveling Assistant. All rights reserved.
  </div>
</footer>

<?php if (!empty($pageScripts)): ?>
    <?php foreach ($pageScripts as $script): ?>
        <script src="/assets/js/<?= htmlspecialchars($script)?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>