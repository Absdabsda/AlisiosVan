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

/* Slugs canónicos por id */
$slugById = [ 1=>'matcha', 2=>'skye', 3=>'rusty', 4=>'tibi' ];

/* Cargamos precios actuales */
$prices = [];
try {
    $st = $pdo->query("SELECT id, price_per_night FROM campers");
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $prices[(int)$row['id']] = (float)$row['price_per_night'];
    }
} catch (Throwable $e) {}

/* SEO: canonical */
$canonical = "https://alisiosvan.com/$lang/campers/";

/* SEO: hreflang */
$hreflangs = [];
foreach ($SUPPORTED_LANGS as $l) {
    $hreflangs[] = '<link rel="alternate" hreflang="'.$l.'" href="https://alisiosvan.com/'.$l.'/campers/" />';
}
$hreflangs[] = '<link rel="alternate" hreflang="x-default" href="https://alisiosvan.com/es/campers/" />';

/* SEO: meta descriptions */
$descriptions = [
    'es' => 'Alquiler de campers en Fuerteventura: furgonetas camper VW clásicas totalmente equipadas. Seguro, asistencia 24/7, cocina, cama doble, ducha exterior y fácil reserva.',
    'en' => 'Camper van rental in Fuerteventura: fully equipped classic VW campers with insurance, 24/7 assistance, kitchen, double bed and outdoor shower.',
    'de' => 'Campervan-Miete auf Fuerteventura: voll ausgestattete klassische VW-Camper mit Versicherung, 24/7-Pannenhilfe, Küche und Doppelbett.',
    'fr' => 'Location de vans aménagés à Fuerteventura : vans VW classiques équipés, assurance et assistance 24/7, cuisine et lit double.',
    'it' => 'Noleggio camper a Fuerteventura: camper VW classici completamente equipaggiati con assicurazione, assistenza 24/7, cucina e letto doppio.',
];
$description = $descriptions[$lang] ?? $descriptions['en'];

/* SEO Block */
$SEO_CAMPERS = <<<HTML
<!-- SEO CAMPERS -->
<meta name="description" content="$description" />
<link rel="canonical" href="$canonical" />
{HREFLANGS}

<!-- Open Graph -->
<meta property="og:title" content="Camper rental in Fuerteventura | Alisios Van" />
<meta property="og:description" content="$description" />
<meta property="og:url" content="$canonical" />
<meta property="og:image" content="https://alisiosvan.com/src/img/og/campers.jpg" />
<meta property="og:type" content="website" />

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="Camper rental in Fuerteventura | Alisios Van" />
<meta name="twitter:description" content="$description" />
<meta name="twitter:image" content="https://alisiosvan.com/src/img/og/campers.jpg" />
HTML;

$SEO_CAMPERS = str_replace('{HREFLANGS}', implode("\n", $hreflangs), $SEO_CAMPERS);
?>
<!doctype html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- ⭐ META TITLE SUPER SEO según idioma -->
    <title>
        <?= ($lang === 'es')
            ? 'Alquiler de furgonetas camper en Fuerteventura | Campers VW clásicas'
            : ($lang === 'en'
                ? 'Camper van rental in Fuerteventura | Classic VW campers'
                : __('Our Campers') . ' | Alisios Van'
            );
        ?>
    </title>

    <?= $SEO_CAMPERS ?>

    <meta name="google" content="notranslate">

    <!-- Fonts & CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
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
        :root { --header-bg-rgb: 133,126,110; }
    </style>
</head>

<body>
<?php include 'inc/header.inc'; ?>

<main>

    <!-- HERO -->
    <section class="page-hero campers-hero">
        <div class="page-hero__content">

            <!-- ⭐ H1 SUPER SEO sin romper diseño -->
            <h1 class="page-hero__title">
                <?= ($lang === 'es')
                    ? 'Alquiler de furgonetas camper en Fuerteventura'
                    : ($lang === 'en'
                        ? 'Camper van rental in Fuerteventura'
                        : __('Our Campers')
                    );
                ?>
            </h1>
        </div>
    </section>

    <!-- Highlights -->
    <section class="fleet-highlights text-white py-3">
        <div class="container d-flex flex-wrap gap-4 justify-content-center text-center small">
            <div><i class="bi bi-geo-alt"></i> <?= __('Pickup in Puerto del Rosario') ?></div>
            <div><i class="bi bi-shield-check"></i> <?= __('Insurance & 24/7 roadside assistance') ?></div>
            <div><i class="bi bi-fuel-pump"></i> <?= __('Fuel-efficient') ?></div>
            <div><i class="bi bi-sun"></i> <?= __('Great weather all year') ?></div>
        </div>
    </section>

    <!-- Intro -->
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

                    <!-- ⭐ SEO extra -->
                    <p class="seo-extra mt-4 small text-muted">
                        <?= ($lang === 'es')
                            ? 'Alquila una camper en Fuerteventura y descubre la isla con total libertad. Nuestras VW T3 y T4 están totalmente equipadas para una experiencia única.'
                            : ($lang === 'en'
                                ? 'Rent a camper van in Fuerteventura and explore the island freely in a fully equipped VW T3 or T4.'
                                : ''
                            );
                        ?>
                    </p>
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
                <button class="model-chip active" data-series=""><?= __('All') ?></button>
                <button class="model-chip" data-series="T3">VW T3</button>
                <button class="model-chip" data-series="T4">VW T4</button>
                <button class="model-chip" data-series="Spacious">Spacious</button>
            </div>
        </div>
    </section>

    <!-- Catalogue -->
    <section class="catalogo-campers py-5">
        <div class="container">
            <div class="row g-4">

                <!-- MATCHA -->
                <?php $id=1; $slug=$slugById[$id]; ?>
                <div class="col-md-4 camper-col">
                    <a class="camper-card d-block"
                       href="/<?= $lang ?>/camper/<?= $slug ?>/"
                       aria-label="<?= __('View details') ?>: Matcha">
                        <img src="/src/img/carousel/matcha-surf.34.32.jpeg"
                             alt="<?= __('ALT_GALLERY_MATCHA') ?>"
                             title="<?= __('TITLE_GALLERY_MATCHA') ?>"
                             loading="lazy">
                        <div class="camper-info">
                            <h3>“Matcha”</h3>
                            <p><?= sprintf(__('From €%s per night'), number_format($prices[$id] ?? 0, 0)) ?></p>
                        </div>
                    </a>
                </div>

                <!-- SKYE -->
                <?php $id=2; $slug=$slugById[$id]; ?>
                <div class="col-md-4 camper-col">
                    <a class="camper-card d-block"
                       href="/<?= $lang ?>/camper/<?= $slug ?>/"
                       aria-label="<?= __('View details') ?>: Skye">
                        <img src="/src/img/carousel/t3-azul-playa.webp"
                             alt="<?= __('ALT_GALLERY_SKYE') ?>"
                             title="<?= __('TITLE_GALLERY_SKYE') ?>"
                             loading="lazy">
                        <div class="camper-info">
                            <h3>“Skye”</h3>
                            <p><?= sprintf(__('From €%s per night'), number_format($prices[$id] ?? 0, 0)) ?></p>
                        </div>
                    </a>
                </div>

                <!-- TIBI -->
                <?php $id=4; $slug=$slugById[$id]; ?>
                <div class="col-md-4 camper-col">
                    <a class="camper-card d-block"
                       href="/<?= $lang ?>/camper/<?= $slug ?>/"
                       aria-label="<?= __('View details') ?>: Tibi">
                        <img src="/src/img/tibi/tibi-feliz.jpeg"
                             alt="<?= __('ALT_GALLERY_TIBI') ?>"
                             title="<?= __('TITLE_GALLERY_TIBI') ?>"
                             loading="lazy">
                        <div class="camper-info">
                            <h3>“Tibi”</h3>
                            <p><?= sprintf(__('From €%s per night'), number_format($prices[$id] ?? 0, 0)) ?></p>
                        </div>
                    </a>
                </div>

                <!-- RUSTY -->
                <?php $id=3; $slug=$slugById[$id]; ?>
                <div class="col-md-4 camper-col">
                    <a class="camper-card d-block"
                       href="/<?= $lang ?>/camper/<?= $slug ?>/"
                       aria-label="<?= __('View details') ?>: Rusty">
                        <img src="/src/img/carousel/t4-sol.webp"
                             alt="<?= __('ALT_GALLERY_RUSTY') ?>"
                             title="<?= __('TITLE_GALLERY_RUSTY') ?>"
                             loading="lazy">
                        <div class="camper-info">
                            <h3>“Rusty”</h3>
                            <p><?= sprintf(__('From €%s per night'), number_format($prices[$id] ?? 0, 0)) ?></p>
                        </div>
                    </a>
                </div>

            </div>
        </div>
    </section>

    <!-- BLOQUE SEO / EDITORIAL MULTILINGÜE -->
    <section class="container py-5" id="campers-seo-block">
        <h2 class="h2 mb-3"><?= __('seo.campers.h2') ?></h2>

        <p><?= __('seo.campers.p1') ?></p>

        <h3 class="h4 mt-4 mb-2"><?= __('seo.campers.h3') ?></h3>
        <ul>
            <li><?= __('seo.campers.li1') ?></li>
            <li><?= __('seo.campers.li2') ?></li>
            <li><?= __('seo.campers.li3') ?></li>
            <li><?= __('seo.campers.li4') ?></li>
        </ul>

        <p class="mt-3"><?= __('seo.campers.p2') ?></p>
    </section>


</main>

<?php include 'inc/footer.inc'; ?>

</body>
</html>
