<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

$cookie = 'admin_key';

// borrar cookie (mismo path/opciones que al crearla)
setcookie($cookie, '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax', // puedes usar 'Strict' si quieres
]);
unset($_COOKIE[$cookie]);

// elige dónde llevar al usuario tras cerrar
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$dest = ($base === '' || $base === '/') ? '/' : $base . '/';
header('Location: ' . $dest, true, 303);
exit;
