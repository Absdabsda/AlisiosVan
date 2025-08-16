<?php
// src/buscar.php
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Available campers | Alisios Van</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">

    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr" defer></script>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" defer></script>

    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/campers.css">
    <link rel="stylesheet" href="css/buscar.css">
    <link rel="stylesheet" href="css/cookies.css">

    <script src="js/header.js" defer></script>
    <script src="js/buscar.js" defer></script>
    <script src="js/cookies.js" defer></script>

    <style>
        :root { --header-bg-rgb: 131,115,100; } /* #837364 */
    </style>

</head>
<!-- Overlay "redirigiendo a Stripe" -->
<div id="checkoutOverlay" hidden>
    <div class="co-box">
        <div class="spinner-border" role="status" aria-hidden="true"></div>
        <p class="mt-3 mb-0">Redirecting you to our secure checkout ...</p>
        <div class="text-muted small">Don't close this window.</div>
    </div>
</div>
<style>
    #checkoutOverlay{
        position:fixed; inset:0; display:none; place-items:center;
        background:rgba(255,255,255,.9); z-index:2000;
    }
    #checkoutOverlay.show{ display:grid; }
    #checkoutOverlay .co-box{
        background:#fff; border:1px solid rgba(0,0,0,.06); border-radius:12px;
        padding:18px 22px; text-align:center; box-shadow:0 10px 30px rgba(0,0,0,.12);
    }
</style>

<body>
<?php include 'inc/header.inc'; ?>

<main>
    <!-- HERO idéntico al de campers -->
    <section class="page-hero buscar-hero">
        <div class="page-hero__content">
            <h1 class="page-hero__title">Available campers</h1>
        </div>
    </section>

    <!-- Barra: Volver + selector de fechas -->
    <section class="py-3 border-top">
        <div class="container d-flex align-items-center justify-content-between flex-wrap gap-2">
            <a id="backLink" class="btn btn-outline-secondary btn-sm" href="index.php">
                <i class="bi bi-arrow-left"></i> Back
            </a>

            <div class="d-flex align-items-center gap-2">
                <label for="dateRange" class="form-label mb-0">Travel dates</label>
                <div class="date-chip">
                    <input
                            type="text"
                            id="dateRange"
                            class="form-control"
                            placeholder="Change dates"
                            autocomplete="off"
                            inputmode="none"
                            readonly
                    />
                </div>
                <i class="bi bi-calendar3"></i>
        </div>

        <div class="container mt-2">
            <p class="text-muted text-center mb-0" id="rangeLabel"></p>
        </div>
    </section>

    <!-- Filtros (mismo estilo que campers.php) -->
    <section class="py-3">
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
            <div id="results" class="row g-4"></div>
            <p class="mt-4 text-muted" id="emptyMsg" style="display:none">No campers available for these dates.</p>
        </div>
    </section>
</main>

<?php include 'inc/footer.inc'; ?>


</body>
</html>
