<?php
// Página actual para resaltar el menú
$__cur = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
function navActive(string $file): string {
    global $__cur; return $__cur === $file ? 'active' : '';
}
?>
<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg bg-body-tertiary" data-bs-theme="light">
    <div class="container-fluid px-3 px-lg-3">
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php" aria-label="Alisios Van — Home">
        <span class="brand-logo-box">
            <img src="img/Alisios (4).png" alt="Alisios Van" class="brand-logo">
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
                    <a class="nav-link <?= navActive('campers.php') ?>" href="campers.php">Campers</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= navActive('sobre-nosotros.php') ?>" href="sobre-nosotros.php">About Us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= navActive('faq.php') ?>" href="faq.php">FAQ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= navActive('contacto.php') ?>" href="contacto.php">Contact</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= navActive('find-reservation.php') ?>" href="find-reservation.php">
                        Manage booking
                    </a>
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
