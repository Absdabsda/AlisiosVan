<?php
// config/i18n-lite.php
declare(strict_types=1);

if (defined('I18N_READY')) return;
define('I18N_READY', true);

// ========================
// Idiomas permitidos
// ========================
$ALLOWED_LANGS = ['es','en','de','fr','it'];

// ========================
// Detectar idioma (?lang= → cookie → 'en')
// ========================
$GET    = strtolower((string)($_GET['lang'] ?? ''));
$cookie = strtolower((string)($_COOKIE['lang'] ?? ''));
$LANG   = 'en';

if ($GET && in_array($GET, $ALLOWED_LANGS, true)) {
    $LANG = $GET;
    if (!headers_sent()) {
        setcookie('lang', $LANG, time()+60*60*24*365, '/', '', false, true);
    }
} elseif ($cookie && in_array($cookie, $ALLOWED_LANGS, true)) {
    $LANG = $cookie;
}

// ========================
// Diccionario global opcional (config/i18n/dict.php)
// Estructura: ['es'=>['English key'=>'traducción',...], 'de'=>[...], ...]
// ========================
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
$externalDictPath = __DIR__ . '/i18n/dict.php';
if (is_file($externalDictPath)) {
    $EXT = include $externalDictPath;
    if (is_array($EXT)) {
        foreach ($EXT as $lang => $map) {
            if (!isset($DICT[$lang])) $DICT[$lang] = [];
            if (is_array($map)) {
                // el externo pisa al base
                $DICT[$lang] = array_replace($DICT[$lang], $map);
            }
        }
    }
}

// ========================
// i18n por página (config/i18n/lang/<lc>/<domain>.php)
// ========================
$LANG_DIR   = __DIR__ . '/i18n/lang';
$_I18N_PAGE = [/* 'es'=>['legal'=>[key=>val]] */];

function i18n_domain(): string {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: ($_SERVER['SCRIPT_NAME'] ?? '');
    $file = basename($path, '.php');
    $dom  = preg_replace('~[^a-z0-9]+~i', '', $file);
    return $dom ?: 'common';
}

function i18n_load_page(string $domain): void {
    global $_I18N_PAGE, $LANG_DIR, $LANG;
    if (isset($_I18N_PAGE[$LANG][$domain])) return;

    // Idioma activo
    $arrLC = [];
    $fLC = "{$LANG_DIR}/{$LANG}/{$domain}.php";
    if (is_file($fLC)) {
        $tmp = include $fLC;
        if (is_array($tmp)) $arrLC = $tmp;
    }

    // Fallback inglés
    $arrEN = [];
    $fEN = "{$LANG_DIR}/en/{$domain}.php";
    if (is_file($fEN)) {
        $tmp = include $fEN;
        if (is_array($tmp)) $arrEN = $tmp;
    }

    $_I18N_PAGE[$LANG][$domain] = $arrLC;
    // guardamos también el fallback en para consultas posteriores
    $_I18N_PAGE['en'][$domain]  = $_I18N_PAGE['en'][$domain] ?? $arrEN;
}

// ========================
// Helpers públicos
// ========================
if (!function_exists('__')) {
    function __(string $s, ...$args): string {
        global $DICT, $LANG, $_I18N_PAGE;
        $domain = i18n_domain();
        i18n_load_page($domain);

        // 1) archivo por página en idioma activo
        $val = $_I18N_PAGE[$LANG][$domain][$s]
            // 2) archivo por página en EN (fallback)
            ?? ($_I18N_PAGE['en'][$domain][$s] ?? null)
            // 3) diccionario global (si existe)
            ?? ($DICT[$LANG][$s] ?? ($DICT['en'][$s] ?? null))
            // 4) clave tal cual
            ?? $s;

        return $args ? vsprintf($val, $args) : $val;
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

// --- Cargar packs por idioma: config/i18n/lang/<LC>/*.php ---
$langDir = __DIR__ . '/i18n/lang/' . $LANG;
if (is_dir($langDir)) {
    foreach (glob($langDir . '/*.php') as $pack) {
        $arr = include $pack;
        if (is_array($arr)) {
            if (!isset($DICT[$LANG])) $DICT[$LANG] = [];
            // Los packs de la carpeta pisan lo anterior (prioridad alta)
            $DICT[$LANG] = array_replace($DICT[$LANG], $arr);
        }
    }
}

// Fallback a inglés si falta una clave
if (!function_exists('__')) {
    function __(string $s): string {
        global $DICT, $LANG;
        return $DICT[$LANG][$s] ?? ($DICT['en'][$s] ?? $s);
    }
}
