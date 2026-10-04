<?php

use Framework\Router;

    require __DIR__ . '/vendor/autoload.php';
    require('helpers.php');

    // $routes = [
    //     '/' => 'controllers/home.php',
    //     '/listings' => 'controllers/listings/index.php',
    //     '/listings/create' => 'controllers/listings/create.php',
    //     '404' => 'controllers/error/404.php',
    // ];

    // $uri = $_SERVER['REQUEST_URI'];

    // if(array_key_exists($uri, $routes)){
    //     require(basePath($routes[$uri]));
    // }

    // if(!array_key_exists($uri, $routes)){
    //     require(basePath($routes['404']));
    // }


    require basePath('Framework/Router.php');
    require basePath('Framework/Database.php');
   
    
    $router = new Router();

    $routes = require basePath('routes.php');

    $uri = $_SERVER['REQUEST_URI'];

    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    $method = $_SERVER['REQUEST_METHOD'];

    $router->route($uri, $method);


?>