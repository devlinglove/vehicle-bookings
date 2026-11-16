<?php

use Framework\Container;
use Framework\TripDb;

$container = new Container();

$container->bind('database', function(){
      $config = require basePath('config/db2.php');
      return new TripDb($config);
});

return $container;

?>
