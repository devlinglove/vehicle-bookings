<?php

    // $routes = [
    //     '/' => 'controllers/home.php',
    //     '/listings' => 'controllers/listings/index.php',
    //     '/listings/create' => 'controllers/listings/create.php',
    //     '404' => 'controllers/error/404.php',
    // ];

    $router->get('/', 'HomeController@index');
    $router->get('/listings', 'ListingController@index');
    $router->get('/listings/create', 'ListingController@create')->only(['auth']);
    $router->get('/listing', 'ListingController@show');
    $router->get('/trips', 'TripsController@index');
    $router->get('/trip', 'TripsController@show');

    $router->get('/auth/login', 'AuthController@login');
    $router->get('/auth/register', 'AuthController@create');
    $router->post('/auth/register', 'AuthController@store');
    $router->post('/auth/login', 'AuthController@authenticate');
    $router->post('/auth/logout', 'AuthController@logout');


    // $router->get('/', 'controllers/home.php');
    // $router->get('/listings', 'controllers/listings/index.php');
    // $router->get('/listing', 'controllers/listings/show.php');

?>