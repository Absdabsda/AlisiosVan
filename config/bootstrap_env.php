<?php
// config/bootstrap_env.php

$root = dirname(__DIR__);

// 1) Autoload de Composer (repo local / producción)
foreach ([
             $root . '/vendor/autoload.php',
             __DIR__ . '/../vendor/autoload.php',
         ] as $auto) {
    if (is_readable($auto)) { require_once $auto; break; }
}

// 2) Intento “automático” de secure/ a partir del DOCUMENT_ROOT (Hostinger)
$docroot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
$maybeSecure = $docroot ? dirname($docroot) . '/secure' : null;
$maybeHostBootstrap = $maybeSecure ? $maybeSecure . '/bootstrap.php' : null;

// 3) Si existe el bootstrap del servidor, úsalo; si no, .env local
if ($maybeHostBootstrap && is_readable($maybeHostBootstrap)) {
    require_once $maybeHostBootstrap;
} else {
    if (class_exists('Dotenv\\Dotenv') && is_readable($root . '/.env')) {
        Dotenv\Dotenv::createImmutable($root)->safeLoad();
    }
    if (!function_exists('env')) {
        function env(string $k, $d=null){
            $v = $_ENV[$k] ?? $_SERVER[$k] ?? getenv($k);
            return ($v === '' || $v === false || $v === null) ? $d : $v;
        }
    }
}
