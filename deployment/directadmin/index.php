<?php

/*
|--------------------------------------------------------------------------
| MikroTik Ashgabat — front controller for DirectAdmin shared hosting
|--------------------------------------------------------------------------
| This file replaces public/index.php inside public_html. The Laravel
| application itself lives OUTSIDE the web root, e.g.:
|
|   /home/USER/mikrotik-app/                     <- Laravel root (.env, app, vendor, storage …)
|   /home/USER/domains/DOMAIN/public_html/       <- contents of Laravel's public/ + this file
|
| Set APP_ROOT below to the path of the Laravel root, relative to this file
| or absolute. Nothing in the Laravel root is reachable from the web.
*/

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

const APP_ROOT = __DIR__.'/../../../mikrotik-app';

define('LARAVEL_START', microtime(true));

if (! is_file(APP_ROOT.'/vendor/autoload.php')) {
    http_response_code(500);
    exit('Application root not found. Check APP_ROOT in public_html/index.php.');
}

if (file_exists($maintenance = APP_ROOT.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require APP_ROOT.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once APP_ROOT.'/bootstrap/app.php';

// public_html is the public directory (uploads, build assets, images).
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
