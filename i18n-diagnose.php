<?php
declare(strict_types=1);
ini_set('display_errors','1');
ini_set('display_startup_errors','1');
error_reporting(E_ALL);

// NO imprimimos nada antes del include para no romper setcookie
$path = __DIR__ . '/config/i18n-lite.php';
$external = __DIR__ . '/config/i18n/dict.php';

if (!is_file($path)) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "NO encuentro config/i18n-lite.php en: $path\n";
    exit;
}

require $path; // aquí se cargarán $LANG, $DICT y las funciones

header('Content-Type: text/plain; charset=utf-8');

echo "PHP: " . PHP_VERSION . "\n";
echo "i18n-lite.php: OK\n";
echo "Idioma detectado (\$_GET ?lang / cookie / por defecto): $LANG\n";
echo "Existe diccionario externo: " . (is_file($external) ? 'sí' : 'NO') . " ($external)\n";

if (is_file($external)) {
    $EXT = include $external;
    echo "El externo devuelve array: " . (is_array($EXT) ? 'sí' : 'NO') . "\n";
    if (is_array($EXT)) {
        echo "Claves externas: " . implode(', ', array_keys($EXT)) . "\n";
        echo "Total strings ES externos: " . (isset($EXT['es']) ? count($EXT['es']) : 0) . "\n";
    }
}

echo "\nPruebas de traducción:\n";
echo " - __('About Us') => " . __('About Us') . "\n";
echo " - __('Manage Booking') => " . __('Manage Booking') . "\n";
echo " - __('FAQ') => " . __('FAQ') . "\n";
echo " - __('Reservation #') => " . __('Reservation #') . "\n";

echo "\nheaders_sent(): " . (headers_sent() ? 'sí' : 'no') . "\n";
echo "Cookie lang (solo visible en la siguiente petición): " . ($_COOKIE['lang'] ?? '(no aún)') . "\n";
