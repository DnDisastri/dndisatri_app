<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// In produzione la document root è public_html e il progetto sta accanto,
// fuori dal web: qui dentro c'è solo il contenuto di public/.
$root = is_file(__DIR__.'/../vendor/autoload.php')
    ? __DIR__.'/..'
    : __DIR__.'/../dndisastri-production';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $root.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $root.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $root.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
