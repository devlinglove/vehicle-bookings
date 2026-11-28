<?php

    // $routes = [
    //     '/' => 'controllers/home.php',
    //     '/listings' => 'controllers/listings/index.php',
    //     '/listings/create' => 'controllers/listings/create.php',
    //     '404' => 'controllers/error/404.php',
    // ];

    $router->get('/', 'HomeController@index');
    // $router->get('/listings', 'ListingController@index');
    // $router->get('/listings/create', 'ListingController@create')->only(['auth']);
    // $router->get('/listing', 'ListingController@show');
    //$router->get('/trips', 'TripsController@index');
    //$router->get('/trip', 'TripsController@show');

    //initiate-payment
    $router->post('/booking', 'HomeController@bookings');
    $router->get('/checkout', 'HomeController@checkout');
    $router->post('/initiate-payment', 'HomeController@initiatePayment');
    $router->get('/success', 'HomeController@success');
    $router->get('/cancel', 'HomeController@cancel');
    
    $router->get('/checkout/selection', 'HomeController@selection');
    $router->get('/voucher', 'HomeController@voucher');
    $router->post('/upload', 'HomeController@upload');

    $router->post('/payment-redirect', 'HomeController@paymentRedirect');

    


    $router->get('/auth/login', 'AuthController@login');
    $router->get('/auth/register', 'AuthController@create');
    $router->post('/auth/register', 'AuthController@store');
    $router->post('/auth/login', 'AuthController@authenticate');
    $router->post('/auth/logout', 'AuthController@logout');

    $router->get('/auth/profile', 'AuthController@profile');
    $router->post('/auth/update-profile', 'AuthController@update');


    $router->get('/admin/login', 'AdminController@login');
    $router->get('/admin/register', 'AdminController@create');
    $router->post('/admin/login', 'AdminController@authenticate');
    $router->post('/admin/register', 'AdminController@store');
    $router->post('/admin/logout', 'AdminController@logout');


    $router->get('/transporters', 'TransporterController@index');
    $router->get('/transporters/create', 'TransporterController@create')->only(['admin']);
    $router->post('/transporters/create', 'TransporterController@store');
    $router->get('/transporters/update', 'TransporterController@update')->only(['admin']);
    $router->post('/transporters/search', 'TransporterController@search');
    $router->post('/transporters/update', 'TransporterController@change');
    $router->get('/transporters/delete', 'TransporterController@delete')->only(['admin']);
    $router->post('/transporters/search-delete', 'TransporterController@searchDelete');
    $router->post('/transporters/delete', 'TransporterController@remove');


    // $router->get('/admin/trips', 'TripController@index');
    // $router->get('/admin/trips/create', 'TripController@create')->only(['admin']);
    // $router->post('/admin/trips/create', 'TripController@store')
    
    
    $router->get('/trips', 'TripsController@index');
    $router->get('/trip', 'TripsController@show');
    $router->get('/admin/trips/create', 'TripsController@create')->only(['admin']);
    $router->post('/admin/trips/create', 'TripsController@store');




?>