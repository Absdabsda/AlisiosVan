<?php
// src/camper.php
$id    = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$start = $_GET['start'] ?? '';
$end   = $_GET['end']   ?? '';
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Camper details | Alisios Van</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Swiper -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>

    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Estilos propios -->
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/ficha-camper.css">

    <!-- Scripts (orden) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr" defer></script>

    <script>
        window.__CAMPPER_PAGE_PROPS__ = {
            id: <?= $id ?>,
            start: "<?= htmlspecialchars($start) ?>",
            end: "<?= htmlspecialchars($end) ?>"
        };
    </script>
    <script src="js/ficha-camper.js" defer></script>

    <style>
        /* ====== Estilos mínimos de layout (igual que los tuyos) ====== */
        .product-wrap{padding:2rem 0;}
        .product-grid{display:grid;grid-template-columns:1.6fr .9fr;gap:2rem;}
        @media (max-width: 992px){.product-grid{grid-template-columns:1fr}}
        .pd-hero{height:42vh;min-height:320px;background-size:cover;background-position:center;position:relative;color:#fff}
        .pd-hero::after{content:"";position:absolute;inset:0;background:rgba(0,0,0,.35)}
        .pd-hero .inner{position:relative;z-index:1;display:flex;height:100%;align-items:end;padding:2rem}
        .pd-title{font-family:'Playfair Display',serif;font-weight:700;font-size:3rem;margin:0}

        .pd-card{position:sticky;top:1rem;background:#fff;border-radius:12px;box-shadow:var(--box-shadow-medium);padding:1.25rem}
        .pd-price{font-weight:700;font-size:1.25rem}
        .pd-total{font-weight:700}
        .badge-soft{background:rgba(128,193,208,.15);color:var(--text-principal);border-radius:999px;padding:.35rem .75rem}
        .amenities{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.5rem 1rem}
        @media (max-width:576px){.amenities{grid-template-columns:1fr}}
        .amenities .item{display:flex;align-items:center;gap:.5rem}
        .gallery .swiper{border-radius:12px;box-shadow:var(--box-shadow-medium);overflow:hidden}
        .thumbs .swiper-slide{opacity:.6;cursor:pointer;border-radius:10px;overflow:hidden}
        .thumbs .swiper-slide-thumb-active{opacity:1;box-shadow:0 0 0 2px var(--color-mar)}
        .section-title{font-family:'Playfair Display',serif;font-weight:700;font-size:1.75rem}

        /* Date chip */
        .date-chip{position:relative;display:inline-block}
        .date-chip > .bi-calendar3{position:absolute;left:12px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--color-mar-oscuro);opacity:.85;z-index:2}
        .date-chip > input#pdDateRange{position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:0!important;border:0!important;clip:rect(0 0 0 0);overflow:hidden;white-space:nowrap}
        .date-chip > input#pdDateRange + input.flatpickr-input{
            border-radius:999px!important;border:1.5px solid var(--color-mar)!important;background:#fff!important;color:var(--text-principal)!important;
            padding:6px 14px 6px 38px!important;min-width:240px;height:38px;font-weight:600;box-shadow:none!important
        }
        .date-chip > input#pdDateRange + input.flatpickr-input:hover{background:rgba(128,193,208,.08)!important;border-color:var(--color-mar-oscuro)!important}
        .date-chip > input#pdDateRange + input.flatpickr-input:focus{outline:none!important;border-color:var(--color-mar-oscuro)!important;box-shadow:0 0 0 3px rgba(128,193,208,.25)!important}
    </style>
</head>
<body>
<?php include 'inc/header.inc'; ?>

<!-- HERO -->
<section class="pd-hero" id="pdHero" style="background-image:url('img/carousel/t3-azul-mar.webp')">
    <div class="inner container">
        <h1 class="pd-title" id="pdTitle">Camper</h1>
    </div>
</section>
<nav class="pd-breadcrumb container">
    <a href="<?= $PREFIX ?? '' ?>campers.php">Campers</a>
    <span>›</span>
    <span id="pdCrumb">Details</span>
</nav>

<main class="product-wrap">
    <div class="container product-grid">
        <!-- Columna izquierda -->
        <div>
            <div class="gallery mb-3">
                <div class="swiper" id="galleryMain">
                    <div class="swiper-wrapper" id="galleryMainWrapper"><!-- slides JS --></div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-pagination" id="galleryPagination"></div>
                </div>
                <div class="swiper mt-2" id="galleryThumbs">
                    <div class="swiper-wrapper" id="galleryThumbsWrapper"><!-- thumbs JS --></div>
                </div>
            </div>

            <div class="mb-3 d-flex flex-wrap gap-2" id="badges"><!-- badges JS --></div>

            <h2 class="section-title mt-4">About this camper</h2>
            <p id="pdDescription">
                A classic van made for island adventures. Comfortable bed, compact kitchen,
                and everything you need to sleep by the ocean under the stars.
            </p>

            <h2 class="section-title mt-4">Amenities</h2>
            <div class="amenities" id="amenities"><!-- amenities JS --></div>
        </div>

        <!-- Columna derecha -->
        <aside>
            <div class="pd-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="pd-price"><span id="price">—</span> €/night</div>
                    <span class="badge-soft" id="seatsBadge"><i class="bi bi-people"></i> 4 seats</span>
                </div>

                <hr class="my-3">

                <label class="form-label small">Travel dates</label>
                <div class="date-chip mb-2">
                    <i class="bi bi-calendar3"></i>
                    <input type="text" id="pdDateRange" placeholder="Choose dates" readonly />
                </div>
                <div class="small text-muted" id="nightsText">Select dates to see total.</div>

                <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-primary flex-fill" id="btnReserve">Reserve</button>
                    <a class="btn btn-outline-secondary" id="btnBack">Back</a>
                </div>

                <div class="mt-3 small text-muted">
                    Free mileage · Basic insurance included · Assistance 24/7
                </div>
            </div>
        </aside>
    </div>
</main>

<!-- Modal checkout -->
<div class="modal fade" id="reserveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="reserveForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Complete your booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rf_camper_id" value="<?= $id ?>">
                <input type="hidden" id="rf_start" value="<?= htmlspecialchars($start) ?>">
                <input type="hidden" id="rf_end"   value="<?= htmlspecialchars($end) ?>">
                <div class="mb-3">
                    <label class="form-label">Full name</label>
                    <input type="text" id="rf_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" id="rf_email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="tel" id="rf_phone" class="form-control">
                </div>
                <div class="small text-muted">
                    You’ll be redirected to our secure checkout to complete the payment.
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" type="submit">Pay & reserve</button>
            </div>
        </form>
    </div>
</div>

<?php include 'inc/footer.inc'; ?>
</body>
</html>
