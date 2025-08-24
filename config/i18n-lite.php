<?php
// config/i18n-lite.php
declare(strict_types=1);

if (defined('I18N_READY')) return;
define('I18N_READY', true);

// Idiomas permitidos
$ALLOWED_LANGS = ['es','en'];

// Detectar idioma: ?lang= → cookie → 'en'
$GET    = strtolower($_GET['lang'] ?? '');
$cookie = strtolower($_COOKIE['lang'] ?? '');
$LANG   = 'en';

if ($GET && in_array($GET, $ALLOWED_LANGS, true)) {
    $LANG = $GET;
    if (!headers_sent()) {
        setcookie('lang', $LANG, time()+60*60*24*365, '/', '', false, true);
    }
} elseif ($cookie && in_array($cookie, $ALLOWED_LANGS, true)) {
    $LANG = $cookie;
}

// Diccionario base mínimo (por si no existe el externo)
$DICT = [
    'es' => [
        'Campers' => 'Campers',
        'About Us' => 'Sobre nosotros',
        'FAQ' => 'FAQ',
        'Contact' => 'Contacto',
        'Manage Booking' => 'Gestionar reserva',
        'Manage your booking' => 'Gestiona tu reserva',
        'Continue' => 'Continuar',
    ],
    'en' => [],
];

// Cargar diccionario externo:  /config/i18n/dict.php
$externalDictPath = __DIR__ . '/i18n/dict.php';
if (is_file($externalDictPath)) {
    $EXT = include $externalDictPath; // debe devolver array
    if (is_array($EXT)) {
        foreach ($EXT as $lang => $map) {
            if (!isset($DICT[$lang])) $DICT[$lang] = [];
            if (is_array($map)) {
                $DICT[$lang] = array_replace($DICT[$lang], $map); // externo pisa al base
            }
        }
    }
}

// Funciones
if (!function_exists('__')) {
    function __(string $s): string {
        global $DICT, $LANG;
        return $DICT[$LANG][$s] ?? $s;
    }
}
if (!function_exists('url_with_lang')) {
    function url_with_lang(string $to): string {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';
        $u      = parse_url($scheme.'://'.$host.$uri);
        $qs     = [];
        if (!empty($u['query'])) parse_str($u['query'], $qs);
        $qs['lang'] = $to;
        $path  = $u['path'] ?? '/';
        $query = http_build_query($qs);
        return $path . ($query ? '?'.$query : '');
    }
}
