<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Campers | Alisios Van</title>

    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/campers.css">

    <script src="js/campers.js" defer></script>
</head>
<body>
<?php include 'inc/header.inc'; ?>


<main>
    <!-- HERO internoo -->
    <section class="page-hero campers-hero">
        <div class="page-hero__content">
            <h1 class="page-hero__title">Our Campers</h1>
        </div>
    </section>

    <!-- Highlights (tira superior) -->
    <section class="fleet-highlights text-white py-3">
        <div class="container d-flex flex-wrap gap-4 justify-content-center text-center small">
            <div class="d-flex align-items-center gap-2"><i class="bi bi-geo-alt"></i> Pick-up at Puerto del Rosario</div>
            <div class="d-flex align-items-center gap-2"><i class="bi bi-shield-check"></i> Seguro e asistencia 24/7</div>
            <div class="d-flex align-items-center gap-2"><i class="bi bi-fuel-pump"></i> Consumo eficiente</div>
            <div class="d-flex align-items-center gap-2"><i class="bi bi-sun"></i> Clima perfecto todo el año</div>
        </div>
    </section>

    <!-- Intro / Qué incluyen -->
    <section class="campers-intro py-5 border-bottom">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <h2 class="h1 mb-3">Classic VW campers, fully equipped</h2>
                    <p class="lead mb-3">
                        Todas nuestras furgos están revisadas y listas para recorrer Fuerteventura.
                        Ideal para parejas o amigos que quieran libertad total.
                    </p>

                    <ul class="icon-list mb-4">
                        <li>Cama para 2, ropa de cama y almohadas</li>
                        <li>Kit de cocina (hornillo, menaje, nevera)</li>
                        <li>Iluminación interior + ducha solar exterior</li>
                        <li>Asesoramiento de rutas y spots para dormir</li>
                    </ul>

                </div>

                <div class="col-lg-5">
                    <div class="p-4 rounded-4 shadow-sm bg-light">
                        <h3 class="h5 mb-3">What’s included</h3>
                        <ul class="small mb-0">
                            <li>Seguro básico y asistencia</li>
                            <li>Km libres en la isla</li>
                            <li>Limpieza y desinfección previa</li>
                            <li>Entrega/recogida flexible (según disponibilidad)</li>
                            <li>Soporte por WhatsApp durante el viaje</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Filtros -->
    <section class="py-3 border-top">
        <div class="container">
            <div id="modelFilters" class="model-filters">
                <button type="button" class="model-chip active" data-series="">All</button>
                <button type="button" class="model-chip" data-series="T3">VW T3</button>
                <button type="button" class="model-chip" data-series="T4">VW T4</button>
            </div>
        </div>
    </section>



    <!-- Catálogo -->
    <section class="catalogo-campers py-5">
        <div class="container">
            <div class="row g-4">
                <!-- Camper 1 -->
                <div class="col-md-4 camper-col">
                    <div class="camper-card" data-name="Matcha" data-series="T3" data-price="115">
                        <img src="img/carousel/matcha-surf.34.32.jpeg" alt="Volkswagen T3 'Matcha' by the beach" loading="lazy">
                        <div class="camper-info">
                            <h3>"Matcha"</h3>
                            <p>115€ per night.</p>
                        </div>
                    </div>
                </div>

                <!-- Camper 2 -->
                <div class="col-md-4 camper-col">
                    <div class="camper-card" data-name="Skye" data-series="T3" data-price="100">
                        <img src="img/carousel/t3-azul-playa.webp" alt="'Skye' parked near the sea" loading="lazy">
                        <div class="camper-info">
                            <h3>"Skye"</h3>
                            <p>100€ per night.</p>
                        </div>
                    </div>
                </div>

                <!-- Camper 3 -->
                <div class="col-md-4 camper-col">
                    <div class="camper-card" data-name="Rusty" data-series="T4" data-price="85">
                        <img src="img/carousel/t4-sol.webp" alt="'Rusty' at sunset" loading="lazy">
                        <div class="camper-info">
                            <h3>"Rusty"</h3>
                            <p>85€ per night.</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <script src="js/campers.js" defer></script>

    </section>
</main>

<?php include 'inc/footer.inc'; ?>
</body>
</html>
