<?php
// ========================================================
// HEADER MULTI-IDIOMA Alisios Van (FIX cambio de idioma)
// ========================================================

// Cargar i18n si aún no está cargado
if (!defined('I18N_READY')) {
    $i18n = __DIR__ . '/../../config/i18n-lite.php'; // subir 2 niveles
    if (is_file($i18n)) require_once $i18n;
}

// Idiomas soportados
$SUPPORTED_LANGS = ['es','en','de','fr','it'];
$lang = strtolower($LANG ?? ($_GET['lang'] ?? 'es'));
if (!in_array($lang, $SUPPORTED_LANGS, true)) $lang = 'es';

// ---------- Detectar path actual ----------
// p.ej. '/es/camper/van-x/' → parts = ['','es','camper','van-x','']
$reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$reqPath = $reqPath === '' ? '/' : $reqPath;
$parts   = explode('/', trim($reqPath, '/')); // sin barras en extremos

// Segmento 0 es el idioma actual (si no está, $parts[0] será primera parte real)
$hasLangPrefix = isset($parts[0]) && in_array(strtolower($parts[0]), $SUPPORTED_LANGS, true);

// $restAfterLang = todo lo que va después del idioma, p.ej. 'camper/van-x' o 'faq' o ''
$restAfterLang = '';
if ($hasLangPrefix) {
    $restAfterLang = implode('/', array_slice($parts, 1));
} else {
    $restAfterLang = implode('/', $parts);
}
$restAfterLang = trim($restAfterLang, '/'); // normalizado

// Para resaltar menú activo seguimos usando solo el primer segmento tras el idioma
$currentPath = $restAfterLang !== '' ? explode('/', $restAfterLang, 2)[0] : '';
function navActive(string $slug): string {
    global $currentPath;
    return $currentPath === $slug ? 'active' : '';
}

// ---------- Query string: whitelist como el router ----------
$QS_WHITELIST = ['start','end','rid','email','session_id','utm_source','utm_medium','utm_campaign','utm_term','utm_content'];
$incomingQs = [];
parse_str($_SERVER['QUERY_STRING'] ?? '', $incomingQs);

function build_lang_href(string $targetLang, string $restAfterLang, array $qsAll, array $whitelist): string {
    $targetLang = strtolower(substr($targetLang, 0, 2));
    $rest = trim($restAfterLang, '/'); // puede ser '' | 'camper/van-x' | 'faq' | ...

    // Barra final solo si hay "rest"
    $restOut = ($rest !== '') ? ($rest . '/') : '';

    // Construir query filtrada
    $keep = [];
    foreach ($whitelist as $k) {
        if (isset($qsAll[$k]) && $qsAll[$k] !== '') $keep[$k] = $qsAll[$k];
    }
    $qs = http_build_query($keep);

    $url = '/' . rawurlencode($targetLang) . '/' . $restOut;
    if ($qs !== '') $url .= '?' . $qs;
    return $url;
}

// Mapeo de banderas
$flagOf = [
    'en' => 'gb',
    'es' => 'es',
    'de' => 'de',
    'fr' => 'fr',
    'it' => 'it'
];
$curFlag = $flagOf[$lang] ?? 'gb';
?>
<!-- =========================================== -->
<!-- NAVBAR -->
<!-- =========================================== -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container-fluid px-3 px-lg-3">

        <!-- Logo → home del idioma actual -->
        <a class="navbar-brand d-flex align-items-center gap-2"
           href="/<?= htmlspecialchars($lang) ?>/"
           aria-label="Alisios Van — Home">
      <span class="brand-logo-box">
        <picture class="brand-picture">
          <source media="(max-width: 991.98px)" srcset="/img/icono.png">
          <img src="/img/alisios-logo.png"
               alt="Alisios Van"
               class="brand-logo"
               width="200"
               height="60"
               loading="eager"
               decoding="async">
        </picture>
      </span>
        </a>

        <!-- Botón hamburguesa -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarMain"
                aria-controls="navbarMain"
                aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menú principal -->
        <div class="collapse navbar-collapse justify-content-end" id="navbarMain">
            <ul class="navbar-nav mb-2 mb-lg-0 align-items-lg-center gap-lg-2">

                <li class="nav-item">
                    <a class="nav-link <?= navActive('campers') ?>"
                       href="/<?= $lang ?>/campers/"><?= __('Campers') ?></a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= navActive('sobre-nosotros') ?>"
                       href="/<?= $lang ?>/sobre-nosotros/"><?= __('About Us') ?></a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= navActive('faq') ?>"
                       href="/<?= $lang ?>/faq/">FAQ</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= navActive('contacto') ?>"
                       href="/<?= $lang ?>/contacto/"><?= __('Contact') ?></a>
                </li>

                <!-- Botón para gestionar reserva -->
                <li class="nav-item">
                    <button class="nav-link btn btn-link p-0"
                            type="button"
                            data-bs-toggle="modal"
                            data-bs-target="#manageBookingModal">
                        <?= __('Manage Booking') ?>
                    </button>
                </li>

                <!-- Enlace a Instagram -->
                <li class="nav-item">
                    <a class="nav-link instagram-link"
                       href="https://www.instagram.com/alisios_van/"
                       target="_blank"
                       rel="noopener noreferrer"
                       title="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                </li>

                <!-- Selector de idioma -->
                <li class="nav-item dropdown ms-lg-2">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2"
                       href="#"
                       id="langMenu"
                       role="button"
                       data-bs-toggle="dropdown"
                       aria-expanded="false">
                        <span class="fi fi-<?= $curFlag ?>"></span>
                        <span class="d-none d-lg-inline text-uppercase"><?= htmlspecialchars($lang) ?></span>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langMenu">
                        <?php foreach ($SUPPORTED_LANGS as $l): ?>
                            <?php
                            // Enlace que conserva el "rest" (incluye 'camper/<slug>' si aplica)
                            $href = build_lang_href($l, $restAfterLang, $incomingQs, $QS_WHITELIST);
                            ?>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 <?= $lang === $l ? 'active' : '' ?>"
                                   href="<?= htmlspecialchars($href) ?>">
                                    <span class="fi fi-<?= $flagOf[$l] ?>"></span>
                                    <?= strtoupper($l) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>

            </ul>
        </div>
    </div>
</nav>

<!-- =========================================== -->
<!-- MODAL: Manage Booking -->
<!-- =========================================== -->
<div class="modal fade" id="manageBookingModal" tabindex="-1"
     aria-labelledby="manageBookingTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">

            <div class="modal-header">
                <h5 class="modal-title" id="manageBookingTitle"><?= __('Manage your booking') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="<?= __('Close') ?>"></button>
            </div>

            <?php
            $manageSlugByLang = [
                'es' => 'gestionar-reserva',
                'en' => 'manage-booking',
                'de' => 'buchung-verwalten',
                'fr' => 'gerer-reservation',
                'it' => 'gestisci-prenotazione',
            ];
            $manageSlug = $manageSlugByLang[$lang] ?? 'gestionar-reserva';
            ?>

            <!-- IMPORTANTE: POST a la ruta bonita por idioma -->
            <form method="post"
                  action="/<?= htmlspecialchars($lang) ?>/<?= htmlspecialchars($manageSlug) ?>/"
                  class="needs-validation"
                  novalidate>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?= __('Reservation #') ?></label>
                        <input type="number" name="rid" class="form-control"
                               required min="1" inputmode="numeric" pattern="[0-9]+">
                        <div class="invalid-feedback"><?= __('Please enter your reservation number.') ?></div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label"><?= __('Email used for the booking') ?></label>
                        <input type="email" name="email" class="form-control" required>
                        <div class="invalid-feedback"><?= __('We need your email to verify it\'s you.') ?></div>
                    </div>

                    <p class="small text-muted mb-0"><?= __('We’ll verify and take you to your booking.') ?></p>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary w-100"><?= __('Continue') ?></button>
                </div>
            </form>

        </div>
    </div>
</div>
