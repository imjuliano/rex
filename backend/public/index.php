<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Application;
use App\Exception\ExceptionHandler;

// PHP notices and warnings must never leak into a JSON response body.
ini_set('display_errors', '0');
error_reporting(E_ALL);

try {
    (new Application())->run();
} catch (Throwable $e) {
    // Last line of defence: anything that escapes Application::run(),
    // including failures while building it.
    ExceptionHandler::fromEnv()->handle($e, [
        'method' => $_SERVER['REQUEST_METHOD'] ?? '-',
        'path' => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH),
    ]);
}
