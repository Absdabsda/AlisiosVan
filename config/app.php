<?php
// config/app.php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Carga el .env que guardaste en /env/.env
$dotenv = Dotenv::createImmutable(dirname(__DIR__) . '/env');
$dotenv->safeLoad();

// helper sencillo
function envv(string $key, $default = null) {
    return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
}
