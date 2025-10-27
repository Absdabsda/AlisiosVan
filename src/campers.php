<?php
// campers.php — listado de campers con precio desde BD + i18n

require_once __DIR__ . '/../config/bootstrap_env.php';
require_once __DIR__ . '/../config/i18n-lite.php';
require_once __DIR__ . '/../config/db.php';

$pdo = get_pdo();

/* Lang actual (lo setea el router) */
$lang = strtolower($LANG ?? ($_GET['lang'] ?? 'es'));
$SUPPORTED_LANGS = ['es','en','de','fr','it'];
if (!in_array($lang, $SUPPORTED_LANGS, true)) { $lang = 'es'; }

/* Slugs canónicos por id (coherentes con /<lang>/camper/<slug>/) */
$slugById = [ 1=>'matcha', 2=>'skye', 3=>'rusty' ];

/* Cargamos precios actuales por id */
$prices = [];
try {
    $st = $pdo->query("SELECT id, price_per_night FROM campers");
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $prices[(int)$row['id']] = (float)$row['price_per_night'];
    }
} catch (Throwable $e) { /* fallback visual */ }
?>
<!doctype html>
<html lang="<?= htmlspecialchars($LANG ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Campers | Alisios Van</title>

    <!-- evita traducción automática de Chrome -->
    <meta name="google" content="notranslate">

    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flag-icons/css/flag-icons.min.css">

    <link rel="stylesheet" href="/src/css/estilos.css">
    <link rel="stylesheet" href="/src/css/header.css">
    <link rel="stylesheet" href="/src/css/campers.css">
    <link rel="stylesheet" href="/src/css/cookies.css">
    <script src="/src/js/header.js" defer></script>
    <script src="/src/js/campers.js" defer></script>

    <style>
        :root { --header-bg-rgb: 133,126,110; } /* #857E6E */
    </style>

</head>
<body>
<?php include 'inc/header.inc'; ?>

<main>
    <!-- HERO -->
    <section class="page-hero campers-hero">
        <div class="page-hero__content">
            <h1 class="page-hero__title"><?= __('Our Campers') ?></h1>
        </div>
    </section>

    <!-- Highlights -->
    <section class="fleet-highlights text-white py-3">
        <div class="container d-flex flex-wrap gap-4 justify-content-center text-center small">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-geo-alt"></i> <?= __('Pickup in Puerto del Rosario') ?>
            </div>
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-shield-check"></i> <?= __('Insurance & 24/7 roadside assistance') ?>
            </div>
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-fuel-pump"></i> <?= __('Fuel-efficient') ?>
            </div>
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-sun"></i> <?= __('Great weather all year') ?>
            </div>
        </div>
    </section>

    <!-- Intro / What's included -->
    <section class="campers-intro py-5 border-bottom">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <h2 class="h1 mb-3"><?= __('Classic VW campers, fully equipped') ?></h2>
                    <p class="lead mb-3">
                        <?= __('All our vans are serviced and ready to explore Fuerteventura—perfect for couples or friends who want total freedom.') ?>
                    </p>

                    <ul class="icon-list mb-4">
                        <li><?= __('Double bed with bed linen and pillows') ?></li>
                        <li><?= __('Kitchen kit (camp stove, cookware, utensils, fridge)') ?></li>
                        <li><?= __('Interior lighting and outdoor solar shower') ?></li>
                    </ul>
                </div>

                <div class="col-lg-5">
                    <div class="p-4 rounded-4 shadow-sm bg-light">
                        <h3 class="h5 mb-3"><?= __('What’s included') ?></h3>
                        <ul class="small mb-0">
                            <li><?= __('Basic insurance and roadside assistance') ?></li>
                            <li><?= __('150 km per day included on the island') ?></li>
                            <li><?= __('Thorough cleaning before each rental') ?></li>
                            <li><?= __('Flexible pickup/return (when available)') ?></li>
                            <li><?= __('WhatsApp support throughout your trip') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Filters -->
    <section class="py-3 border-top">
        <div class="container">
            <div id="modelFilters" class="model-filters">
                <button type="button" class="model-chip active" data-series=""><?= __('All') ?></button>
                <button type="button" class="model-chip" data-series="T3">VW T3</button>
                <button type="button" class="model-chip" data-series="T4">VW T4</button>
            </div>
        </div>
    </section>

    <!-- Catalogue -->
    <section class="catalogo-campers py-5">
        <div class="container">
            <div class="row g-4">

                <!-- Camper 1 -->
                <?php $id=1; $slug=$slugById[$id]; ?>
                <div class="col-md-4 camper-col">
                    <a class="camper-card d-block text-decoration-none"
                       href="/<?= htmlspecialchars($lang) ?>/camper/<?= htmlspecialchars($slug) ?>/"
                       data-id="<?= $id ?>" data-name="Matcha" data-series="T3"
                       data-price="<?= htmlspecialchars((string)($prices[$id] ?? 0)) ?>"
                       aria-label="<?= __('View details') ?>: “Matcha”">
                        <img src="/src/img/carousel/matcha-surf.34.32.jpeg"
                             alt="<?= __('Volkswagen T3 “Matcha” by the beach') ?>" loading="lazy">
                        <div class="camper-info">
                            <h3 class="mb-1">“Matcha”</h3>
                            <p class="mb-0">
                                <?= sprintf(__('From €%s per night'), number_format((float)($prices[$id] ?? 0), 0)) ?>
                            </p>
                        </div>
                    </a>
                </div>

                <!-- Camper 2 -->
                <?php $id=2; $slug=$slugById[$id]; ?>
                <div class="col-md-4 camper-col">
                    <a class="camper-card d-block text-decoration-none"
                       href="/<?= htmlspecialchars($lang) ?>/camper/<?= htmlspecialchars($slug) ?>/"
                       data-id="<?= $id ?>" data-name="Skye" data-series="T3"
                       data-price="<?= htmlspecialchars((string)($prices[$id] ?? 0)) ?>"
                       aria-label="<?= __('View details') ?>: “Skye”">
                        <img src="/src/img/carousel/t3-azul-playa.webp"
                             alt="<?= __('“Skye” parked near the sea') ?>" loading="lazy">
                        <div class="camper-info">
                            <h3 class="mb-1">“Skye”</h3>
                            <p class="mb-0">
                                <?= sprintf(__('From €%s per night'), number_format((float)($prices[$id] ?? 0), 0)) ?>
                            </p>
                        </div>
                    </a>
                </div>

                <!-- Camper 3 -->
                <?php $id=3; $slug=$slugById[$id]; ?>
                <div class="col-md-4 camper-col">
                    <a class="camper-card d-block text-decoration-none"
                       href="/<?= htmlspecialchars($lang) ?>/camper/<?= htmlspecialchars($slug) ?>/"
                       data-id="<?= $id ?>" data-name="Rusty" data-series="T4"
                       data-price="<?= htmlspecialchars((string)($prices[$id] ?? 0)) ?>"
                       aria-label="<?= __('View details') ?>: “Rusty”">
                        <img src="/src/img/carousel/t4-sol.webp"
                             alt="<?= __('“Rusty” at sunset') ?>" loading="lazy">
                        <div class="camper-info">
                            <h3 class="mb-1">“Rusty”</h3>
                            <p class="mb-0">
                                <?= sprintf(__('From €%s per night'), number_format((float)($prices[$id] ?? 0), 0)) ?>
                            </p>
                        </div>
                    </a>
                </div>

            </div>
        </div>
    </section>
</main>

<?php include 'inc/footer.inc'; ?>
</body>
</html>
