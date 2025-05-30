<?php

require '../vendor/autoload.php';
require '../app/routes.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();

Flight::path(__DIR__.'/../app/controllers');
Flight::path(__DIR__.'/../app/middlewares');

$db = "pgsql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']}";
Flight::register(
    'db',
    \flight\database\PdoWrapper::class,
    [$db, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]]
);

if ($_ENV['ENVIRONMENT'] !== 'development') {
    Flight::set('flight.log_errors', true);
    Flight::map('notFound', function () {
        Flight::jsonHalt(['error' => '请求的路径不存在'], 404);
    });
    Flight::map('error', function (Throwable $error) {
        Flight::jsonHalt(['error' => '服务器内部错误'], 500);
    });

    Flight::before('start', function () {
        Flight::response()->header('X-Frame-Options', 'SAMEORIGIN');
        Flight::response()->header("Content-Security-Policy", "default-src 'self'");
        Flight::response()->header('X-XSS-Protection', '1; mode=block');
        Flight::response()->header('X-Content-Type-Options', 'nosniff');
        Flight::response()->header('Referrer-Policy', 'no-referrer-when-downgrade');
        Flight::response()->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        Flight::response()->header('Permissions-Policy', 'geolocation=()');
    });
} else {
    Flight::before('start', function () {
        Flight::response()->header('Access-Control-Allow-Origin', 'http://localhost:5173');
        Flight::response()->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        Flight::response()->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        Flight::response()->header('Access-Control-Allow-Credentials', 'true');
    });
    Flight::route('OPTIONS *', function () {
        Flight::response()->status(200);
        return false;
    });
}