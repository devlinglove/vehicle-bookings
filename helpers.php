<?php

use Cloudinary\Cloudinary;
use Framework\Session;

function basePath($path = '')
{
    return __DIR__ . '/' . $path;
}


function loadPartialView(string $name, array $data = [])
{
    $viewPath = basePath("App/views/partials/$name.php");

    if (!file_exists($viewPath)) {
        echo "File with name $name does not exist";
        return;
    }

    extract($data);
    require $viewPath;
}

function loadView(string $name, $data = [])
{
    $viewPath = basePath("App/views/$name.view.php");
    if (!file_exists($viewPath)) {
        echo "File with name $name does not exists";
        return;
    }
    extract($data);
    require $viewPath;
}


function inspect(mixed $value)
{
    echo '<pre>';
    var_dump($value);
    echo '</pre>';
}

function formatSalary(int $value)
{
    return '$' . number_format(floatval($value));
}

function dd($value)
{
    echo '<pre>';
    die(var_dump($value));
    echo '</pre>';
}


function redirect($url)
{
  header("Location: {$url}");
  exit;
}


function seatClass($status)
{
    $base = 'seat relative w-[46px] min-w-[46px] h-[46px] min-h-[46px] not-mobile:w-[38px] not-mobile:min-w-[38px] not-mobile:h-[38px] not-mobile:min-h-[38px] flex items-center justify-center rounded-xl text-sm sm:text-base font-bold focus:outline-none focus-visible:ring-4';

    if ($status == 'booked') {
        return $base . ' bg-navy-600 text-white cursor-not-allowed';
    }

    if ($status == 'hold') {
        return $base . ' border-2 border-navy-600 bg-navy-50 text-navy-700 focus-visible:ring-navy-100';
    }

    if ($status == 'available') {
        return $base . ' border-2 border-slate-200 bg-white text-slate-700 hover:border-navy-600 hover:shadow-md focus-visible:ring-navy-100';
    }

    return $base;
}


function failTo(string $url, string $message): void
{
    Session::set('checkout_error', $message);
    header('Location: ' . $url);
    exit;
}


function cloudinary(): Cloudinary
{
    static $client = null;

    $configPath = basePath('config/cloudinary.php');

    if (!file_exists($configPath)) {
        die('Cloudinary config file not found!');
    }

    $cloudinary = require $configPath;

    return $client ??= $cloudinary;
}


// function cloudinary(): Cloudinary
// {
//     static $client = null;
//     return $client ??= require basePath('config/cloudinary.php');
// }