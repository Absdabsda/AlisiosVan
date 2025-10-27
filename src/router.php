<?php
/**
 * Router principal de Alisios Van
 * - Normaliza lang y path
 * - Alias multi-idioma → slug canónico interno
 * - Enruta a ficheros canónicos
 * - Slugs canónicos "bonitos" por idioma (PREFERRED) para la URL final
 * - Maneja detalle /<lang>/camper/<slug>/
 * - 404 si no hay match
 */
declare(strict_types=1);

// ---------------------------------------------------------
// 1) Idioma
// ---------------------------------------------------------
$lang = strtolower($_GET['lang'] ?? 'es');
$GLOBALS['LANG'] = $lang;
if (!isset($_COOKIE['lang']) || $_COOKIE['lang'] !== $lang) {
    // cookie 1 año (httpOnly)
    setcookie('lang', $lang, time() + 31536000, '/', '', false, true);
}

// ---------------------------------------------------------
// 2) Path (prioriza 'path'; acepta 'slug' legacy y combinaciones page/child)
// ---------------------------------------------------------
$path = trim($_GET['path'] ?? '', '/');

if ($path === '' && isset($_GET['slug'])) {
    $path = trim((string)$_GET['slug'], '/'); // compat con htaccess anterior
}

if ($path === '' && (isset($_GET['page']) || isset($_GET['child']))) {
    if (($_GET['page'] ?? '') === 'camper-detail' && isset($_GET['child'])) {
        $path = 'camper/' . trim((string)$_GET['child'], '/');
    }
}

// Normaliza a minúsculas y convierte espacios en guiones
$pathNorm = strtolower($path);
$pathNorm = preg_replace('~\s+~', '-', $pathNorm ?: '');

// Buscar con fechas bonitas: /<lang>/buscar/YYYY-MM-DD/YYYY-MM-DD/
if (preg_match('~^buscar/(\d{4}-\d{2}-\d{2})/(\d{4}-\d{2}-\d{2})$~', $pathNorm, $m)) {
    $_GET['start'] = $m[1];
    $_GET['end']   = $m[2];
    require __DIR__ . '/buscar.php';
    exit;
}

// ---------------------------------------------------------
// 3) Alias → slug canónico interno (claves del array $ROUTES)
// ---------------------------------------------------------
$ALIASES = [
    // Home
    ''                  => 'home',
    'home'              => 'home',
    'inicio'            => 'home',

    // Buscar / Search
    'buscar'            => 'buscar',
    'search'            => 'buscar',
    'recherche'         => 'buscar',
    'suche'             => 'buscar',
    'cerca'             => 'buscar',

    // Campers
    'campers'           => 'campers',

    // Sobre nosotros
    'sobre-nosotros'    => 'sobre-nosotros',
    'about-us'          => 'sobre-nosotros',
    'uber-uns'          => 'sobre-nosotros',
    'über-uns'          => 'sobre-nosotros',
    'a-propos'          => 'sobre-nosotros',
    'chi-siamo'         => 'sobre-nosotros',

    // FAQ / Ayuda
    'faq'               => 'faq',
    'preguntas-frecuentes' => 'faq',
    'ayuda'             => 'faq',
    'help'              => 'faq',
    'hilfe'             => 'faq',
    'aide'              => 'faq',
    'domande-frequenti' => 'faq',

    // Contacto
    'contacto'          => 'contacto',
    'contact'           => 'contacto',
    'kontakt'           => 'contacto',
    'contactez-nous'    => 'contacto',
    'contatti'          => 'contacto',

    // Gestionar reserva
    'gestionar-reserva' => 'gestionar-reserva',
    'manage-booking'    => 'gestionar-reserva',
    'buchung-verwalten' => 'gestionar-reserva',
    'gerer-reservation' => 'gestionar-reserva',
    'gérer-réservation' => 'gestionar-reserva',
    'gestisci-prenotazione' => 'gestionar-reserva',

    // Legales y políticas
    'terms'             => 'terms',
    'legal'             => 'legal',
    'privacy'           => 'privacy',
    'cookie'            => 'cookie',
    'cookies'           => 'cookie', // alias

    // Gracias / Cancel
    'thanks'            => 'thanks',
    'gracias'           => 'thanks',
    'danke'             => 'thanks',
    'merci'             => 'thanks',
    'grazie'            => 'thanks',

    'cancel'            => 'cancel',
    'cancelado'         => 'cancel',
    'storniert'         => 'cancel',
    'annule'            => 'cancel',
    'annullato'         => 'cancel',

    // ES
    'terminos-y-condiciones'       => 'terms',
    'aviso-legal'                  => 'legal',
    'politica-de-privacidad'       => 'privacy',
    'politica-de-cookies'          => 'cookie',

    // EN
    'terms-and-conditions'         => 'terms',
    'legal-notice'                 => 'legal',
    'privacy-policy'               => 'privacy',
    'cookie-policy'                => 'cookie',

    // DE
    'agb'                          => 'terms',
    'impressum'                    => 'legal',
    'datenschutz'                  => 'privacy',
    'cookie-richtlinie'            => 'cookie',

    // FR
    'conditions-generales'         => 'terms',
    'mentions-legales'             => 'legal',
    'politique-de-confidentialite' => 'privacy',
    'politique-de-cookies'         => 'cookie',

    // IT
    'termini-e-condizioni'         => 'terms',
    'note-legali'                  => 'legal',
    'informativa-privacy'          => 'privacy',
    'informativa-cookie'           => 'cookie',

    // Buscar/Find (ya tienes alias → 'gestionar-reserva')
    'manage'               => 'manage',
    'manage-booking-link'  => 'manage',          // EN
    'gestion-reserva-link' => 'manage',          // ES
    'verwaltung-link'      => 'manage',          // DE
    'gestion-lien'         => 'manage',          // FR
    'gestisci-link'        => 'manage',          // IT

];

// ---------------------------------------------------------
// 4) Slug canónico interno → fichero PHP
// ---------------------------------------------------------
$ROUTES = [
    'home'               => 'index.php',
    'campers'            => 'campers.php',
    'buscar'             => 'buscar.php',
    'sobre-nosotros'     => 'sobre-nosotros.php',
    'faq'                => 'faq.php',
    'contacto'           => 'contacto.php',
    'gestionar-reserva'  => 'find-reservation.php',
    'terms'              => 'terms.php',
    'legal'              => 'legal.php',
    'privacy'            => 'privacy.php',
    'cookie'             => 'cookies.php',
    'thanks'             => 'thanks.php',
    'cancel'             => 'cancel.php',
    'manage'             => 'manage.php',


];

// ---------------------------------------------------------
// 5) Detalle de camper: /<lang>/camper/<slug>/
// ---------------------------------------------------------
if (preg_match('~^camper/([^/]+)$~', $pathNorm, $m)) {
    $_GET['child'] = $m[1];
    require __DIR__ . '/camper-detail.php';
    exit;
}

// ---------------------------------------------------------
// 6) Resolver alias y redirigir a canónica (localizada por idioma)
// ---------------------------------------------------------
$slug = $ALIASES[$pathNorm] ?? null;

// Slug preferido por idioma para cada página (claves = keys de $ROUTES)
$PREFERRED = [
    'es' => [
        'home' => 'home',
        'buscar' => 'buscar',
        'campers' => 'campers',
        'sobre-nosotros' => 'sobre-nosotros',
        'faq' => 'faq',
        'contacto' => 'contacto',
        'gestionar-reserva' => 'gestionar-reserva',
        'terms' => 'terminos-y-condiciones',
        'legal' => 'aviso-legal',
        'privacy' => 'politica-de-privacidad',
        'cookie' => 'politica-de-cookies',
        'thanks' => 'gracias',
        'cancel' => 'cancelado',
        'manage' => 'gestion-reserva-link',
    ],
    'en' => [
        'home' => 'home',
        'buscar' => 'search',
        'campers' => 'campers',
        'sobre-nosotros' => 'about-us',
        'faq' => 'faq',
        'contacto' => 'contact',
        'gestionar-reserva' => 'manage-booking',
        'terms' => 'terms-and-conditions',
        'legal' => 'legal-notice',
        'privacy' => 'privacy-policy',
        'cookie' => 'cookie-policy',
        'thanks' => 'thanks',
        'cancel' => 'cancel',
        'manage' => 'manage-booking-link',
    ],
    'de' => [
        'home' => 'home',
        'buscar' => 'suche',
        'campers' => 'campers',
        'sobre-nosotros' => 'uber-uns',   // o 'über-uns'
        'faq' => 'faq',
        'contacto' => 'kontakt',
        'gestionar-reserva' => 'buchung-verwalten',
        'terms' => 'agb',                 // Allgemeine Geschäftsbedingungen
        'legal' => 'impressum',
        'privacy' => 'datenschutz',
        'cookie' => 'cookie-richtlinie',
        'thanks' => 'danke',
        'cancel' => 'storniert',
        'manage' => 'verwaltung-link',
    ],
    'fr' => [
        'home' => 'home',
        'buscar' => 'recherche',
        'campers' => 'campers',
        'sobre-nosotros' => 'a-propos',
        'faq' => 'faq',
        'contacto' => 'contact',          // o 'contactez-nous'
        'gestionar-reserva' => 'gerer-reservation',
        'terms' => 'conditions-generales',
        'legal' => 'mentions-legales',
        'privacy' => 'politique-de-confidentialite',
        'cookie' => 'politique-de-cookies',
        'thanks' => 'merci',
        'cancel' => 'annule',
        'manage' => 'gestion-lien',
    ],
    'it' => [
        'home' => 'home',
        'buscar' => 'cerca',
        'campers' => 'campers',
        'sobre-nosotros' => 'chi-siamo',
        'faq' => 'faq',
        'contacto' => 'contatti',
        'gestionar-reserva' => 'gestisci-prenotazione',
        'terms' => 'termini-e-condizioni',
        'legal' => 'note-legali',
        'privacy' => 'informativa-privacy',
        'cookie' => 'informativa-cookie',
        'thanks' => 'grazie',
        'cancel' => 'annullato',
        'manage' => 'gestisci-link',
    ],
];


// Tolerancia extra: si viniera con espacios (muy legacy)
if ($slug === null) {
    $try = str_replace('-', ' ', $pathNorm);
    if (isset($ALIASES[$try])) $slug = $ALIASES[$try];
}

if ($slug && isset($ROUTES[$slug])) {
    $preferred = $PREFERRED[$lang][$slug] ?? $slug;
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // Solo redirige si NO es POST (GET/HEAD ok); en POST sirve el fichero directamente
    if ($pathNorm !== $preferred && $method !== 'POST') {
        $location = '/' . rawurlencode($lang) . '/' . $preferred . '/';
        if (!empty($_SERVER['QUERY_STRING'])) {
            $location .= '?' . $_SERVER['QUERY_STRING'];
        }
        header('Location: ' . $location, true, 302);
        exit;
    }

    require __DIR__ . '/' . $ROUTES[$slug];
    exit;
}




// Soporte legacy: si llega con .php en el path, redirige a la bonita
if (preg_match('~^(.+)\.php$~', $pathNorm, $mm)) {
    $pathNorm = $mm[1];
    header('Location: /' . rawurlencode($lang) . '/' . $pathNorm . '/?' . ($_SERVER['QUERY_STRING'] ?? ''), true, 301);
    exit;
}

// ---------------------------------------------------------
// 7) Compat extra: si llegaran .php por query legacy
// ---------------------------------------------------------
$legacyPhpMap = [
    'about us'              => 'sobre-nosotros.php',
    'contacto.php'          => 'contacto.php',
    'faq.php'               => 'faq.php',
    'find-reservation.php'  => 'find-reservation.php',
];
if (isset($legacyPhpMap[$path])) {
    $targetSlug = array_search($legacyPhpMap[$path], $ROUTES, true);
    if ($targetSlug) {
        $preferred = $PREFERRED[$lang][$targetSlug] ?? $targetSlug;
        header('Location: /' . rawurlencode($lang) . '/' . $preferred . '/', true, 301);
        exit;
    }
    require __DIR__ . '/' . $legacyPhpMap[$path];
    exit;
}

// ---------------------------------------------------------
// 8) 404
// ---------------------------------------------------------
http_response_code(404);
require __DIR__ . '/404.php';
