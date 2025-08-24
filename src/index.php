<?php
require_once __DIR__ . '/../config/bootstrap_env.php';
require_once __DIR__ . '/../config/i18n-lite.php';
?>
<!doctype html>
<html lang="<?= htmlspecialchars($LANG ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alisios Van</title>

    <!-- evita traducción automática de Chrome -->
    <meta name="google" content="notranslate">

    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" defer></script>

    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flag-icons/css/flag-icons.min.css">

    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <?php if ($LANG==='es'): ?>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
        <script>window.FLATPICKR_LOCALE = flatpickr.l10ns.es;</script>
    <?php endif; ?>

    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/landing.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/cookies.css">
    <script src="js/header.js" defer></script>
    <script src="js/landing.js" defer></script>
    <script src="js/cookies.js" defer></script>
    <style>
        :root{ --header-bg-rgb: 167,176,183; }
    </style>
</head>
<body>
<?php include 'inc/header.inc'; ?>

<div class="landing-hero" style="--header-bg-rgb: 129,193,208;">
    <img src="img/matcha-landing-page.jpeg" alt="Landscape Camper Landing Image">
    <div class="landing-overlay">
        <h1 class="landing-title"><?= __('Freedom is not a place') ?></h1>
        <p class="subheading-title"><?= __('It’s the journey you choose') ?></p>

        <div class="search-wrapper">
            <form class="search-form row align-items-center justify-content-center" id="searchForm">
                <!-- Ubicación -->
                <div class="col-md-4">
                    <label for="location" class="form-label"><?= __('Where are you going?') ?></label>
                    <input type="text" class="form-control" id="location" placeholder="<?= __('Fuerteventura') ?>" readonly>
                </div>

                <div class="col-md-6">
                    <label for="date-range" class="form-label"><?= __('Travel dates') ?></label>
                    <input type="text" id="date-range" class="form-control" placeholder="<?= __('Choose your travel dates') ?>" readonly />
                </div>

                <!-- Botón de búsqueda -->
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-lg mt-3"><?= __('Search') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- BENEFICIOS -->
<section class="info-section py-5">
    <div class="container text-center">
        <h2 class="section-title"><?= __('Why travel with Alisios Van?') ?></h2>
        <div class="row mt-4">
            <div class="col-md-4">
                <i class="fas fa-map-marked-alt fa-2x mb-2"></i>
                <h4><?= __('Explore freely') ?></h4>
                <p><?= __('No limits. Discover the Canary Islands at your own pace.') ?></p>
            </div>
            <div class="col-md-4">
                <i class="fas fa-sun fa-2x mb-2"></i>
                <h4><?= __('Always sunny') ?></h4>
                <p><?= __('Enjoy good weather all year round.') ?></p>
            </div>
            <div class="col-md-4">
                <i class="fas fa-campground fa-2x mb-2"></i>
                <h4><?= __('Sleep anywhere') ?></h4>
                <p><?= __('Beach, mountains or forest — your bed travels with you.') ?></p>
            </div>
        </div>
    </div>
</section>

<!-- TESTIMONIOS -->
<section class="testimonials-section py-5 bg-light">
    <h2 class="section-title text-center"><?= __('What our travelers say') ?></h2>

    <div class="swiper testimonials-swiper mt-4">
        <div class="swiper-wrapper">
            <!-- Dejo los testimonios tal cual (multilenguaje real) -->
            <div class="swiper-slide">
                <p>"I traveled around the Canary Islands, between Fuerteventura and Lanzarote, for 10 days and rented Carlos’ van for this trip. Carlos was very helpful and friendly throughout my stay, always ready to solve any doubts or needs I had. Even though I don’t speak Spanish, we were able to communicate easily in English. I’m grateful for the experience he made possible thanks to his van, which allowed me to enjoy a super pleasant and comfortable holiday. The van was well-equipped with everything I needed and was clean. I would highly recommend it to anyone looking to enjoy a road trip and a van life experience!"</p>
                <div class="nombre">
                    <strong>- Valerio, Italy</strong>
                    <div class="review-stars">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                    </div>
                </div>
            </div>
            <div class="swiper-slide">
                <p>"Carlos es una persona encantadora y muy resolutiva, muy agradecido por su trato excelente. Repetiremos sin duda"</p>
                <div class="nombre">
                    <strong>- Juan, Spain</strong>
                    <div class="review-stars">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                    </div>
                </div>
            </div>
            <div class="swiper-slide">
                <p>"Abbiamo passato 10 giorni in van tra Fuerteventura e Lanzarote, esplorarle in van ha reso tutto più indimenticabile!"</p>
                <div class="nombre">
                    <strong>- Libe, Italy</strong>
                    <div class="review-stars">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                    </div>
                </div>
            </div>
            <div class="swiper-slide">
                <p>"Experiencia inolvidable! Carlos súper atento y amable, la camper tiene todo lo necesario para vivir la experiencia completa. Es preciosa y cómoda, se conduce muy bien aunque sea antigua. Repetiria la experiencia sin duda."</p>
                <div class="nombre">
                    <strong>- María, Spain</strong>
                    <div class="review-stars">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                    </div>
                </div>
            </div>
            <div class="swiper-slide">
                <p>"È andato tutto alla grande, Van pulito ordinato e perfettamente attrezzato, esperienza straordinaria! Carlos il proprietario è una persona eccezionale, disponibile e molto gentile."</p>
                <div class="nombre">
                    <strong>- Omar, Italy</strong>
                    <div class="review-stars">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                    </div>
                </div>
            </div>
            <div class="swiper-slide">
                <p>"¡Viaje imprescindible!"</p>
                <div class="nombre">
                    <strong>- Julia, Spain</strong>
                    <div class="review-stars">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="swiper-button-next"></div><div class="swiper-button-prev"></div><div class="swiper-pagination"></div>
    </div>
</section>

<!-- GALERÍA -->
<section class="gallery-section py-5">
    <div class="container text-center">
        <h2 class="section-title"><?= __('Vanlife in the Canary Islands') ?></h2>
        <div class="row mt-4">
            <div class="col-md-4 mb-3">
                <img src="img/carousel/matcha.34.32 (1).jpeg" class="img-fluid rounded" alt="Camper 1">
            </div>
            <div class="col-md-4 mb-3">
                <img src="img/carousel/t3-azul-mar.webp" class="img-fluid rounded" alt="Camper 2">
            </div>
            <div class="col-md-4 mb-3">
                <img src="img/carousel/t4-sol.webp" class="img-fluid rounded" alt="Camper 3">
            </div>
        </div>
    </div>
</section>

<!-- MAPA -->
<section class="map-section py-5 bg-light">
    <div class="container text-center">
        <h2 class="section-title"><?= __('We are here') ?></h2>
        <div class="map-container mt-4">
            <iframe
                    src="https://www.google.com/maps?q=Puerto+del+Rosario,+Fuerteventura&hl=<?= $LANG==='es'?'es':'en' ?>&z=13&output=embed"
                    width="100%" height="400" style="border:0;"
                    allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </div>
</section>

<!-- CTA final -->
<section class="cta-section text-center py-5">
    <div class="container">
        <h2 class="section-title"><?= __('Ready for your van adventure?') ?></h2>
        <button id="ctaBook" class="btn btn-primary btn-lg mt-3"><?= __('Book Now') ?></button>
    </div>
</section>

<?php include 'inc/footer.inc'; ?>
</body>
</html>
