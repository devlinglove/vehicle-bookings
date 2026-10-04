<!-- <script src="/assets/js/socket.js"></script> -->
<!-- <script src="/assets/js/trip-show.js"></script> -->

<?php

/**
 * @var object $pageScripts
 * @var object $trip_id
 */

// var_dump($pageScripts)

?>

<?php if (!empty($pageScripts)): ?>
    <?php foreach ($pageScripts as $script): ?>
        <script src="/assets/js/<?= htmlspecialchars($script)?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>

</html>