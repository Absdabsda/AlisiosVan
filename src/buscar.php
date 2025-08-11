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

    <script src="js/buscar.js" defer></script>
</head>
<body>
<?php include 'inc/header.inc'; ?>

<main class="py-4">
    <div class="container">
        <h1 class="mb-3">Available campers</h1>
        <p class="text-muted" id="rangeLabel"></p>

        <!-- Filtro por modelo -->
        <div class="mb-3">
            <button class="btn btn-sm btn-outline-secondary me-2 series-chip active" data-series="">All</button>
            <button class="btn btn-sm btn-outline-secondary me-2 series-chip" data-series="T3">VW T3</button>
            <button class="btn btn-sm btn-outline-secondary me-2 series-chip" data-series="T4">VW T4</button>
        </div>

        <div id="results" class="row g-4"></div>

        <p class="mt-4 text-muted" id="emptyMsg" style="display:none">No campers available for these dates.</p>
    </div>
</main>

<!-- Modal de reserva (datos del cliente) -->
<div class="modal fade" id="reserveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="reserveForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Complete your booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rf_camper_id">
                <input type="hidden" id="rf_start">
                <input type="hidden" id="rf_end">
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
                <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" type="submit">Pay & reserve</button>
            </div>
        </form>
    </div>
</div>

<?php include 'inc/footer.inc'; ?>


</body>
</html>
