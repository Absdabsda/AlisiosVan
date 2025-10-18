<?php
$lang = $_GET['lang'] ?? 'es';

// Normaliza entrada: prioriza 'path', pero acepta 'slug' legacy y 'page/child'
$path = trim($_GET['path'] ?? '', '/');
if ($path === '' && isset($_GET['slug'])) {
    $path = trim($_GET['slug'], '/'); // compat con htaccess anterior
}
if ($path === '' && (isset($_GET['page']) || isset($_GET['child']))) {
    if (($_GET['page'] ?? '') === 'camper-detail' && isset($_GET['child'])) {
        $path = 'camper/' . trim($_GET['child'], '/');
    }
}

// Persiste idioma en cookie 1 año
if (!isset($_COOKIE['lang']) || $_COOKIE['lang'] !== $lang) {
    setcookie('lang', $lang, time()+31536000, '/', '', false, true);
}

switch ($path) {
    case '':
    case 'home':
        require __DIR__ . '/index.php';
        break;

    case 'campers':
        require __DIR__ . '/campers.php';
        break;

    default:
        // Detalle: /es/camper/<slug>/
        if (preg_match('~^camper/([^/]+)$~', $path, $m)) {
            $_GET['child'] = $m[1];
            require __DIR__ . '/camper-detail.php';
            break;
        }

        // 404
        http_response_code(404);
        require __DIR__ . '/404.php';
}
