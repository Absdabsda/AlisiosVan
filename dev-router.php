<?php
// dev-router.php — robusto para php -S
$uri  = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$root = __DIR__;

// Helper: intenta servir un asset desde varias raíces posibles
function serve_asset(string $rel, array $roots): bool {
    // Si llega sin extensión, prueba .css y .js
    if (!str_contains(basename($rel), '.')) {
        foreach (['.css','.js'] as $ext) {
            foreach ($roots as $r) {
                $try = rtrim($r, '/') . $rel . $ext;
                if (is_file($try)) {
                    header('Content-Type: ' . (str_ends_with($ext,'.css') ? 'text/css' : 'application/javascript'));
                    readfile($try);
                    return true;
                }
            }
        }
    }
    foreach ($roots as $r) {
        $path = rtrim($r, '/') . $rel;
        if (is_file($path)) {
            $mime = function_exists('mime_content_type') ? (mime_content_type($path) ?: 'application/octet-stream') : 'application/octet-stream';
            header('Content-Type: ' . $mime);
            readfile($path);
            return true;
        }
    }
    return false;
}

// Donde buscar assets (ajusta si usas otra carpeta como /public)
$ASSET_ROOTS = [$root, $root . '/src'];

/* 0) Assets en raíz: /css|/js|/img|/fonts|/assets/... */
if (preg_match('~^/(css|js|img|fonts|assets)/(.*)$~i', $uri, $m)) {
    $rel = '/' . $m[1] . '/' . $m[2];
    if (serve_asset($rel, $ASSET_ROOTS)) exit;
    http_response_code(404); exit('Asset not found');
}

/* 1) Si el fichero físico existe bajo document root, que lo sirva php -S */
$physical = realpath($root . $uri);
if ($physical && str_starts_with($physical, $root) && is_file($physical)) {
    return false;
}

/* 2) ?lang=xx -> /xx/ */
if ($uri === '/' && isset($_GET['lang']) && preg_match('~^(es|en|de|it|fr)$~i', $_GET['lang'])) {
    header('Location: /' . strtolower($_GET['lang']) . '/', true, 301);
    exit;
}

/* 3) Assets con prefijo de idioma (rutas profundas): /es/.../css/... -> /css/... */
if (preg_match('~^/(es|en|de|it|fr)/(?:.+/)?(css|js|img|fonts|assets)/(.*)$~i', $uri, $m)) {
    $rel = '/' . $m[2] . '/' . $m[3];
    if (serve_asset($rel, $ASSET_ROOTS)) exit;
    http_response_code(404); exit('Asset not found');
}

/* 4) /<lang>/ -> src/index.php */
if (preg_match('~^/(es|en|de|it|fr)/?$~i', $uri, $m)) {
    $_GET['lang'] = strtolower($m[1]);
    require $root . '/src/index.php';
    exit;
}

// /<lang>/buscar/YYYY-MM-DD/YYYY-MM-DD/ → src/buscar.php
if (preg_match('~^/(es|en|de|it|fr)/buscar/(\d{4}-\d{2}-\d{2})/(\d{4}-\d{2}-\d{2})/?$~i', $uri, $m)) {
    $_GET['lang']  = strtolower($m[1]);
    $_GET['start'] = $m[2];
    $_GET['end']   = $m[3];
    require $root . '/src/buscar.php';
    exit;
}

// /<lang>/(thanks|cancel)/ -> src/thanks.php / src/cancel.php
if (preg_match('~^/(es|en|de|it|fr)/(thanks|cancel)/?$~i', $uri, $m)) {
    $_GET['lang'] = strtolower($m[1]);
    require $root . '/src/' . ($m[2] === 'thanks' ? 'thanks.php' : 'cancel.php');
    exit;
}



/* 5) /<lang>/<path>/ -> src/router.php */
if (preg_match('~^/(es|en|de|it|fr)/(.+?)/?$~i', $uri, $m)) {
    $_GET['lang'] = strtolower($m[1]);
    $_GET['path'] = $m[2];
    require $root . '/src/router.php';
    exit;
}

/* 6) raíz -> /es/ */
if ($uri === '/' || $uri === '') {
    header('Location: /es/', true, 301);
    exit;
}

/* 7) fallback a /src/... si existe */
$target = $root . '/src' . $uri;
if (is_file($target)) {
    require $target;
    exit;
}

/* 8) 404 */
http_response_code(404);
echo "Not Found";
