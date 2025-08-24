<?php
// Cargar i18n si aún no está cargado
if (!defined('I18N_READY')) {
    $i18n = __DIR__ . '/../../config/i18n-lite.php'; // subir 2 niveles
    if (is_file($i18n)) require_once $i18n;
}

// Página actual para resaltar el menú
$__cur = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
function navActive(string $file): string {
    global $__cur; return $__cur === $file ? 'active' : '';
}
?>
<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container-fluid px-3 px-lg-3">
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php" aria-label="Alisios Van — Home">
          <span class="brand-logo-box">
            <picture class="brand-picture">
              <source media="(max-width: 991.98px)" srcset="img/icono.png">
              <img src="img/alisios-logo.png" alt="Alisios Van" class="brand-logo" width="200" height="60" loading="eager" decoding="async">
            </picture>
          </span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarMain" aria-controls="navbarMain"
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="navbarMain">
            <ul class="navbar-nav mb-2 mb-lg-0 align-items-lg-center gap-lg-2">
                <li class="nav-item">
                    <a class="nav-link <?= navActive('campers.php') ?>" href="campers.php"><?= __('Campers') ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= navActive('sobre-nosotros.php') ?>" href="sobre-nosotros.php"><?= __('About Us') ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= navActive('faq.php') ?>" href="faq.php">FAQ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= navActive('contacto.php') ?>" href="contacto.php"><?= __('Contact') ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#manageBookingModal">
                        <?= __('Manage Booking') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link instagram-link" href="https://www.instagram.com/alisios_van/" target="_blank" rel="noopener noreferrer" title="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                </li>

                <!-- Selector ES/EN -->
                <?php $cur = $LANG ?? 'en';
                $flagOf = [
                    'en' => 'gb',   // usa GB o US, elige la que prefieras
                    'es' => 'es',
                    'de' => 'de',
                    'fr' => 'fr',
                    'it' => 'it',
                ];
                $curFlag = isset($flagOf[$cur]) ? $flagOf[$cur] : 'gb';
                ?>

                <li class="nav-item dropdown ms-lg-2">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2"
                       href="#" id="langMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="fi fi-<?= $curFlag ?>"></span>
                        <span class="d-none d-lg-inline text-uppercase"><?= htmlspecialchars($cur) ?></span>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langMenu">
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 <?= $cur==='es'?'active':'' ?>"
                               href="<?= htmlspecialchars(url_with_lang('es')) ?>">
                                <span class="fi fi-es"></span> Español
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 <?= $cur==='en'?'active':'' ?>"
                               href="<?= htmlspecialchars(url_with_lang('en')) ?>">
                                <span class="fi fi-gb"></span> English
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 <?= $cur==='de'?'active':'' ?>"
                               href="<?= htmlspecialchars(url_with_lang('de')) ?>">
                                <span class="fi fi-de"></span> Deutsch
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 <?= $cur==='fr'?'active':'' ?>"
                               href="<?= htmlspecialchars(url_with_lang('fr')) ?>">
                                <span class="fi fi-fr"></span> Français
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 <?= $cur==='it'?'active':'' ?>"
                               href="<?= htmlspecialchars(url_with_lang('it')) ?>">
                                <span class="fi fi-it"></span> Italiano
                            </a>
                        </li>
                    </ul>
                </li>


            </ul>
        </div>
    </div>
</nav>

<!-- Manage Booking Modal -->
<div class="modal fade" id="manageBookingModal" tabindex="-1" aria-labelledby="manageBookingTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">

            <!-- ÚNICO header -->
            <div class="modal-header">
                <h5 class="modal-title" id="manageBookingTitle"><?= __('Manage your booking') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= __('Close') ?>"></button>
            </div>

            <!-- Formulario SIN otro header dentro -->
            <form action="find-reservation.php" method="get" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?= __('Reservation #') ?></label>
                        <input type="number" name="rid" class="form-control" required min="1" inputmode="numeric" pattern="[0-9]+">
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

