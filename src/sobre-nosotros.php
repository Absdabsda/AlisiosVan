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
    <?php
    // Idioma actual con fallback correcto (igual que en campers)
    $lang = strtolower($LANG ?? ($_GET['lang'] ?? 'es'));

    // URL canonical
    $canonical = "https://alisiosvan.com/$lang/sobre-nosotros/";

    // Idiomas soportados (los mismos que usas en toda la web)
    $supportedLangs = ['es','en','de','fr','it'];

    // META DESCRIPTION por idioma
    $descriptions = [
        'es' => 'Conoce Alisios Van: un proyecto familiar hecho con cariño en Fuerteventura. Preparación propia, atención cercana y campers creadas para disfrutar del viaje.',
        'en' => 'Meet Alisios Van: a family-run project in Fuerteventura. Carefully prepared campers, personal attention and a passion for slow, meaningful travel.',
        'de' => 'Lerne Alisios Van kennen: ein familiengeführtes Projekt auf Fuerteventura. Sorgfältig vorbereitete Camper und persönliche Betreuung für bewusstes Reisen.',
        'fr' => 'Découvrez Alisios Van : un projet familial à Fuerteventura. Des vans préparés avec soin et un service proche pour un voyage en toute sérénité.',
        'it' => 'Scopri Alisios Van: un progetto familiare a Fuerteventura. Camper preparati con cura e attenzione personale per un viaggio autentico e rilassato.',
    ];

    $metaDescription = $descriptions[$lang] ?? $descriptions['en'];

    // Construimos hreflang
    $hreflangs = "";
    foreach ($supportedLangs as $l) {
        $hreflangs .= '<link rel="alternate" hreflang="'.$l.'" href="https://alisiosvan.com/'.$l.'/sobre-nosotros/" />'."\n";
    }
    $hreflangs .= '<link rel="alternate" hreflang="x-default" href="https://alisiosvan.com/es/sobre-nosotros/" />';
    ?>

    <!-- SEO -->
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
    <?= $hreflangs ?>

    <!-- Open Graph -->
    <meta property="og:title" content="<?= __('About Us | Alisios Van') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:image" content="https://alisiosvan.com/src/img/carlos-recogiendo-skye.jpeg">
    <meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
    <meta property="og:type" content="article">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= __('About Us | Alisios Van') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="twitter:image" content="https://alisiosvan.com/src/img/carlos-recogiendo-skye.jpeg">


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
                <img src="/src/img/carlos-recogiendo-skye.jpeg" alt="<?= __('about.img.preparing') ?>" title="<?= __('about.img.preparing') ?>"
                >
            </div>
            <div class="about-final-text">
                <h4><?= __('Made with care, made for you.') ?></h4>
                <p><?= __('Every Alisios Van is prepared with care by our team so every detail is ready for your next adventure. We believe in slow, mindful travel enjoying the journey as much as the destination.') ?></p>
                <p><?= __('From maintenance to cleaning, we put our hearts into making sure your camper feels like a home on wheels.') ?></p>
                <a href="/<?= htmlspecialchars($LANG ?? 'es') ?>/contacto/" class="btn"><?= __('Tell us about your trip') ?></a>
            </div>
        </div>
    </section>
</main>

<?php include 'inc/footer.inc'; ?>
</body>
</html>
