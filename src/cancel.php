<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require_once __DIR__ . '/../config/bootstrap_env.php';
require __DIR__ . '/../config/db.php';

use Stripe\StripeClient;

$pdo = get_pdo();

$rid   = (int)($_GET['rid'] ?? 0);
$token = $_GET['t']  ?? ''; // token de gestión (manage_token) que pusiste en manageUrl

if ($rid < 1) {
    http_response_code(400);
    echo 'Missing or invalid reservation id.';
    exit;
}

try {
    // Traer la reserva (incluye manage_token para validar)
    $st = $pdo->prepare("SELECT id, status, stripe_session_id, manage_token FROM reservations WHERE id = :id LIMIT 1");
    $st->execute([':id' => $rid]);
    $res = $st->fetch(PDO::FETCH_ASSOC);

    if (!$res) {
        http_response_code(404);
        echo 'Reservation not found.';
        exit;
    }

    // Validar token si existe en la reserva
    if (!empty($res['manage_token'])) {
        if (!$token || !hash_equals((string)$res['manage_token'], (string)$token)) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    }

    $status    = (string)$res['status'];
    $sessionId = (string)($res['stripe_session_id'] ?? '');

    $changed = false;
    $message = '';

    // Si está pendiente, se cancela
    if ($status === 'pending') {
        $up = $pdo->prepare("
            UPDATE reservations
               SET status='cancelled_by_customer',
                   cancelled_at=NOW()
             WHERE id=:id AND status='pending'
             LIMIT 1
        ");
        $up->execute([':id' => $rid]);
        $changed = $up->rowCount() > 0;

        // Intentar expirar la Checkout Session en Stripe
        $stripeSecret = env('STRIPE_SECRET');
        if ($sessionId && $stripeSecret) {
            try {
                $stripe = new StripeClient($stripeSecret);
                $stripe->checkout->sessions->expire($sessionId);
            } catch (\Throwable $e) {
                error_log("Failed to expire session $sessionId: " . $e->getMessage());
            }
        }

        $message = $changed
            ? 'Your reservation has been successfully cancelled.'
            : 'We couldn’t cancel the reservation (its status may have changed).';
    } else {
        // Otros estados: solo informar
        $nonCancelable = ['paid', 'confirmed', 'confirmed_deposit', 'paid_deposit'];
        if (in_array($status, $nonCancelable, true)) {
            $message = 'This reservation is already confirmed. To cancel it, please contact us.';
        } elseif ($status === 'cancelled' || $status === 'cancelled_by_customer') {
            $message = 'This reservation was already cancelled.';
        } else {
            $message = 'This reservation cannot be cancelled from this page.';
        }
    }

} catch (\Throwable $e) {
    http_response_code(500);
    echo "Error: " . htmlspecialchars($e->getMessage());
    exit;
}

// Mostrar estado en la página
$isCancelled  = ($status === 'cancelled' || $status === 'cancelled_by_customer' || !empty($changed));
$title        = $isCancelled ? 'Reservation cancelled' : 'Reservation status';
$icon         = $isCancelled ? 'check-circle' : 'info-circle';
$accentClass  = $isCancelled ? 'ok' : 'warn';
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= $title ?> | Alisios Van</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/cookies.css">
    <script src="js/cookies.js" defer></script>

    <style>
        .page-hero{ background-image:url('img/matcha-landing-page.jpeg'); }
        .wrap{ max-width: 840px; margin-inline:auto; padding: var(--spacing-l); }
        .cardy{
            background: var(--color-blanco);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow-medium);
            border: 1px solid rgba(0,0,0,.04);
            padding: clamp(1rem, 2vw, 1.5rem);
        }
        .state{
            display:flex; align-items:center; gap:.75rem;
            background: rgba(255,255,255,.6);
            border:1px solid rgba(0,0,0,.06);
            border-radius: 12px;
            padding:.75rem 1rem;
        }
        .state i{
            font-size: 1.4rem;
            color: var(--color-blanco);
            width: 36px; height:36px; display:grid; place-items:center; border-radius:999px;
        }
        .state i.ok{ background: var(--color-mar); }
        .state i.warn{ background: var(--color-atardecer); }
    </style>
</head>
<body>

<section class="page-hero">
    <div class="page-hero__content">
        <h1 class="page-hero__title"><?= $title ?> #<?= (int)$rid ?></h1>
        <p class="mt-2">Alisios Van</p>
    </div>
</section>

<main class="wrap">
    <div class="cardy">
        <div class="state mb-3">
            <i class="bi bi-<?= $icon ?> <?= $accentClass ?>"></i>
            <div class="flex-grow-1">
                <strong><?= $isCancelled ? 'Your reservation has been cancelled' : 'Your reservation information'; ?></strong>
                <div class="text-muted"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a class="btn" href="index.php"><i class="bi bi-house-door"></i> Back to Home</a>
            <a class="btn btn-outline-secondary" href="campers.php"><i class="bi bi-search"></i> Browse campers</a>
            <a class="btn btn-outline-secondary" href="contact.php"><i class="bi bi-envelope"></i> Contact us</a>
            <?php if(!$isCancelled && $status === 'pending'): ?>
                <a class="btn btn-danger" href="/checkout/retry.php?rid=<?= (int)$rid ?>"><i class="bi bi-arrow-repeat"></i> Retry payment</a>
            <?php endif; ?>
        </div>
    </div>
</main>

</body>
</html>
