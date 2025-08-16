<?php
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
              <!-- Logo para móvil (≤ 991.98px) -->
              <source media="(max-width: 991.98px)" srcset="img/icono.png">
                <!-- Fallback / desktop -->
              <img
                      src="img/logo-original.png"
                      alt="Alisios Van"
                      class="brand-logo"
                      width="200" height="60"
                loading="eager" decoding="async">
            </picture>
          </span>
        </a>


        <!-- Hamburguesa -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarMain" aria-controls="navbarMain"
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menú -->
        <div class="collapse navbar-collapse justify-content-end" id="navbarMain">
            <ul class="navbar-nav mb-2 mb-lg-0 align-items-lg-center gap-lg-2">
                <li class="nav-item">
                    <a class="nav-link <?= navActive('campers.php') ?>"
                       <?php if (navActive('campers.php')): ?>aria-current="page"<?php endif; ?>
                       href="campers.php">Campers</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= navActive('sobre-nosotros.php') ?>"
                       <?php if (navActive('sobre-nosotros.php')): ?>aria-current="page"<?php endif; ?>
                       href="sobre-nosotros.php">About Us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= navActive('faq.php') ?>"
                       <?php if (navActive('faq.php')): ?>aria-current="page"<?php endif; ?>
                       href="faq.php">FAQ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= navActive('contacto.php') ?>"
                       <?php if (navActive('contacto.php')): ?>aria-current="page"<?php endif; ?>
                       href="contacto.php">Contact</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= navActive('find-reservation.php') ?>"
                       <?php if (navActive('find-reservation.php')): ?>aria-current="page"<?php endif; ?>
                       href="find-reservation.php">Manage Booking</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link instagram-link" href="https://www.instagram.com/alisios_van/"
                       target="_blank" rel="noopener noreferrer" title="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
