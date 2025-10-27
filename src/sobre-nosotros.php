<?php
declare(strict_types=1);
require __DIR__ . '/../config/i18n-lite.php';
?>
<!doctype html>
<html lang="<?= htmlspecialchars($LANG ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __('About Us | Alisios Van') ?></title>

    <meta name="google" content="notranslate">

    <link href="https://fonts.googleapis.com/css2?family=Amatic+SC:wght@400;700&family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Seaweed+Script&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Rock+Salt&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" defer></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flag-icons/css/flag-icons.min.css">

    <link rel="stylesheet" href="/src/css/estilos.css">
    <link rel="stylesheet" href="/src/css/header.css">
    <link rel="stylesheet" href="/src/css/sobre-nosotros.css">
    <link rel="stylesheet" href="/src/css/cookies.css">

    <script src="/src/js/header.js" defer></script>
    <script src="/src/js/campers.js" defer></script>
    <script src="/src/js/cookies.js" defer></script>

    <style>
        :root { --header-bg-rgb: 37,50,48; } /* #253230 */
    </style>
</head>
<body>
<?php include 'inc/header.inc'; ?>

<main>
    <section class="page-hero about-us-hero">
        <div class="page-hero__content">
            <h1 class="page-hero__title"><?= __('About Us') ?></h1>
        </div>
    </section>

    <!-- Texto explicativo Sobre Nosotros -->
    <div class="sobre-nosotros container">
        <div class="contenido">
            <div class="texto">
                <p><?= __('about.lead.1') ?></p>
                <p><?= __('about.lead.2') ?></p>
                <p><?= __('about.lead.3') ?></p>
            </div>
        </div>
    </div>

    <!-- Misión, Visión, Valores -->
    <div class="valores-corporativos container">
        <div class="row justify-content-center text-center g-4">
            <div class="col-md-4">
                <div class="valor-box">
                    <h3><?= __('about.mission.h') ?></h3>
                    <p><?= __('about.mission.p') ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="valor-box">
                    <h3><?= __('about.vision.h') ?></h3>
                    <p><?= __('about.vision.p') ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="valor-box text-center">
                    <h3><?= __('about.values.h') ?></h3>
                    <ul class="list-unstyled text-center mx-auto" style="max-width:28rem;">
                        <li class="mb-2"><?= __('about.values.li1') ?></li>
                        <li class="mb-2"><?= __('about.values.li2') ?></li>
                        <li class="mb-2"><?= __('about.values.li3') ?></li>
                        <li class="mb-0"><?= __('about.values.li4') ?></li>
                    </ul>
                </div>
            </div>

        </div>
    </div>

    <!-- Bloque final -->
    <section class="about-final-block">
        <div class="container about-final-container">
            <div class="about-final-image">
                <img src="/src/img/carlos-recogiendo-skye.jpeg" alt="<?= __('Preparing our camper van') ?>">
            </div>
            <div class="about-final-text">
                <h4><?= __('Made with care, made for you.') ?></h4>
                <p><?= __('Every Alisios Van is prepared with care by our team so every detail is ready for your next adventure. We believe in slow, mindful travel enjoying the journey as much as the destination.') ?></p>
                <p><?= __('From maintenance to cleaning, we put our hearts into making sure your camper feels like a home on wheels.') ?></p>
                <a href="contacto.php" class="btn"><?= __('Tell us about your trip') ?></a>
            </div>
        </div>
    </section>
</main>

<?php include 'inc/footer.inc'; ?>
</body>
</html>
