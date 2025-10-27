<?php
// ========================================================
// HEADER MULTI-IDIOMA Alisios Van
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

// Detectar path actual (ej: 'campers', 'contacto', etc.)
$reqPath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$parts = explode('/', $reqPath);       // ej: ['es','campers']
$currentPath = $parts[1] ?? '';        // 'campers' o ''
$currentPathPart = $currentPath ? $currentPath . '/' : '';

// Función para resaltar el menú activo
function navActive(string $slug): string {
    global $currentPath;
    return $currentPath === $slug ? 'active' : '';
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
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 <?= $lang === $l ? 'active' : '' ?>"
                                   href="/<?= $l ?>/<?= htmlspecialchars($currentPathPart) ?>">
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
<!-- MODAL: Manage Booking -->
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

            <!-- IMPORTANTE: ahora POST y a la ruta bonita por idioma -->
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
