<?php
// ficha-camper.php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$campers = [
    1 => [
        'name'   => '“Matcha”',
        'series' => 'VW T3',
        'images' => [
            'img/matcha-fondo-montanya.jpeg',
            'img/carousel/matcha.34.32 (1).jpeg',
            'img/matcha/matcha-ventana.jpeg',
            'img/matcha/matcha-frente.jpeg',
            'img/matcha/matcha-puerta.jpeg',
            'img/matcha/matcha-asiento.jpeg',
            'img/matcha/matcha-cocina.jpeg',
            'img/matcha/matcha-cama.jpeg',
            'img/matcha/matcha-abierta-playa.jpeg',
            'img/matcha-landing-page.jpeg',
        ],
        'seats'  => 5,
        'sleeps' => 3,
        'desc'   => 'Spacious green T3—great for friends or a small family. Simple to drive, fully equipped for island adventures.',
        'baby_seat' => true,
        'features' => [
            '5 travel seats','Sleeps 3','Baby seat can be included','Equipped kitchen: hob, sink & fridge',
            'Cookware & utensils included','Outdoor shower','Solar panel','Camping table & chairs',
        ],
    ],
    2 => [
        'name'   => '“Skye”',
        'series' => 'VW T3',
        'images' => [
            'img/skye-horizontal.JPG',
            'img/carousel/t3-azul-mar.webp',
            'img/skye/skye-lado.jpeg',
            'img/skye/skye-interior-cocina.jpeg',
            'img/skye/skye-sofa.jpeg',
            'img/skye/interior-lateral.05.44 (4).jpeg',
            'img/skye/interior.05.45.jpeg',
            'img/skye/interior-delante.05.44 (2).jpeg',
            'img/carousel/t3-azul-playa.webp',
        ],
        'seats'  => 2,
        'sleeps' => 2,
        'desc'   => 'Our blue T3 is an easy-going classic for two. Compact, comfy and ready for slow travel days.',
        'features' => [
            '2 travel seats','Sleeps 2','Equipped kitchen: hob, sink & fridge',
            'Cookware & utensils included','Outdoor shower','Solar panel','Camping table & chairs',
        ],
    ],
    3 => [
        'name'   => '“Rusty”',
        'series' => 'VW T4',
        'images' => [
            'img/carousel/t4-lejos.webp',
            'img/carousel/t4-sol.webp',
            'img/rusty/abierta-chica.58.21.jpeg',
            'img/rusty/vista-amarilla.58.20 (2).jpeg',
            'img/rusty/playa-oscura.58.20.jpeg',
            'img/rusty/cocina.58.20 (1).jpeg',
            'img/rusty/noche-abierta.58.21 (1).jpeg',
            'img/rusty/retrovisor.58.21 (2).jpeg',
        ],
        'seats'  => 2,
        'sleeps' => 2,
        'desc'   => 'A reliable T4 with a cosy setup for two—compact kitchen, outdoor shower and solar for off-grid stops.',
        'features' => [
            '2 travel seats','Sleeps 2','Equipped kitchen: hob, sink & fridge',
            'Cookware & utensils included','Outdoor shower','Solar panel','Camping table & chairs','Projector',
        ],
    ],
];

$camper = $campers[$id] ?? $campers[1];
$hero   = $camper['images'][0] ?? 'img/carousel/t3-azul-mar.webp';
?>
<!doctype html>
<html lang="<?= htmlspecialchars($LANG ?? 'en') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= htmlspecialchars($camper['series'].' '.$camper['name']) ?> | Alisios Van</title>

    <!-- evita traducción automática de Chrome -->
    <meta name="google" content="notranslate">

    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" defer></script>

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <!-- Swiper -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flag-icons/css/flag-icons.min.css">

    <!-- CSS propio -->
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/ficha-camper.css">
    <link rel="stylesheet" href="css/cookies.css">

    <!-- JS propio -->
    <script src="js/header.js" defer></script>
    <script src="js/cookies.js" defer></script>
    <script src="js/ficha-camper.js" defer></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const thumbs = new Swiper('#galleryThumbs', {
                slidesPerView: 4, spaceBetween: 8, freeMode: true, watchSlidesProgress: true,
                breakpoints: { 0:{slidesPerView:4}, 576:{slidesPerView:5} }
            });
            new Swiper('#galleryMain', {
                slidesPerView: 1,
                spaceBetween: 0,
                navigation: { nextEl: '#galleryMain .swiper-button-next', prevEl: '#galleryMain .swiper-button-prev' },
                pagination: { el: '#galleryPagination', clickable: true },
                thumbs: { swiper: thumbs },
                preloadImages: false, lazy: { loadPrevNext: true }
            });
        });
    </script>

    <?php
    $headerPalette = [
        1 => '131,115,100',
        2 => '82,118,159',
        3 => '167,176,183',
    ];
    $headerRgb = $headerPalette[$id] ?? '131,115,100';
    ?>
    <style>:root{ --header-bg-rgb: <?= htmlspecialchars($headerRgb) ?>; }</style>
</head>
<body>
<?php include 'inc/header.inc'; ?>

<!-- HERO -->
<section class="page-hero camper-detail-hero" style="background-image:url('<?= htmlspecialchars($hero) ?>')">
    <div class="page-hero__content">
        <h1 class="page-hero__title"><?= htmlspecialchars($camper['series'].' '.$camper['name']) ?></h1>
    </div>
</section>

<nav class="pd-breadcrumb container">
    <a href="campers.php"><?= __('Campers') ?></a>
    <span>›</span>
    <span><?= __('Details') ?></span>
</nav>

<main class="camper-detail-wrap">
    <div class="container">
        <div class="detail-grid">
            <!-- IZQUIERDA: GALERÍA -->
            <div class="detail-media">
                <div class="gallery mb-4">
                    <div class="swiper" id="galleryMain">
                        <div class="swiper-wrapper">
                            <?php foreach (array_slice($camper['images'], 1) as $img): ?>
                                <div class="swiper-slide">
                                    <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($camper['name']) ?>">
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($camper['images']) < 2): ?>
                                <div class="swiper-slide">
                                    <img src="<?= htmlspecialchars($hero) ?>" alt="<?= htmlspecialchars($camper['name']) ?>">
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                        <div class="swiper-pagination" id="galleryPagination"></div>
                    </div>

                    <div class="swiper mt-2" id="galleryThumbs">
                        <div class="swiper-wrapper">
                            <?php foreach (array_slice($camper['images'], 1) as $img): ?>
                                <div class="swiper-slide">
                                    <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($camper['name'].' '.__('thumbnail')) ?>">
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($camper['images']) < 2): ?>
                                <div class="swiper-slide">
                                    <img src="<?= htmlspecialchars($hero) ?>" alt="<?= htmlspecialchars($camper['name'].' '.__('thumbnail')) ?>">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DERECHA: INFO -->
            <aside class="detail-aside">
                <div class="detail-card">
                    <div class="detail-head">
                        <div class="badges">
                            <span class="badge-soft"><i class="bi bi-people"></i> <?= (int)$camper['seats'].' '.__('travel seats') ?></span>
                            <span class="badge-soft"><i class="bi bi-moon-stars"></i> <?= __('Sleeps').' '.(int)$camper['sleeps'] ?></span>
                            <span class="badge-soft"><i class="bi bi-sun"></i> <?= __('Solar') ?></span>
                            <span class="badge-soft"><i class="bi bi-droplet"></i> <?= __('Outdoor shower') ?></span>
                            <?php if (!empty($camper['baby_seat'])): ?>
                                <span class="badge-soft"><i class="bi bi-baby"></i> <?= __('Baby seat on request') ?></span>
                            <?php endif; ?>
                        </div>

                        <p class="lead mb-0"><?= htmlspecialchars(__($camper['desc'])) ?></p>
                    </div>

                    <hr>

                    <h2 class="section-title"><?= __('What you’ll find onboard') ?></h2>
                    <ul class="icon-list">
                        <?php foreach ($camper['features'] as $f): ?>
                            <li><?= htmlspecialchars(__($f)) ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="cta-row">
                        <button id="btnReserve" class="btn btn-primary" data-id="<?= (int)$id ?>"><?= __('Reserve') ?></button>
                        <a id="btnBack" class="btn btn-outline-secondary" href="campers.php"><?= __('Back') ?></a>
                    </div>

                    <p class="mini-note"><?= __('150 km/day included · Basic insurance · 24/7 roadside assistance') ?></p>
                </div>
            </aside>
        </div>
    </div>
</main>

<?php include 'inc/footer.inc'; ?>
</body>
</html>
