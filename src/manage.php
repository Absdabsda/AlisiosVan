<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__.'/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__.'/../env')->safeLoad();
require __DIR__.'/../config/db.php';
$pdo = get_pdo();

use Stripe\Stripe;
use Stripe\Refund;
use Stripe\PaymentIntent;

Stripe::setApiKey($_ENV['STRIPE_SECRET'] ?? '');

// --- Helpers ---------------------------------------------------------------
function admin_key(): ?string {
    $envKey = $_ENV['ADMIN_KEY'] ?? '';
    if (!$envKey) return null;

    // lee del GET o de la cookie
    $k = $_GET['key'] ?? ($_COOKIE['admin_key'] ?? '');
    if ($k && hash_equals($envKey, (string)$k)) {
        // recuerda al admin 30 días (HttpOnly)
        if (empty($_COOKIE['admin_key'])) {
            setcookie('admin_key', $k, time()+60*60*24*30, '/', '', false, true);
        }
        return (string)$k;
    }
    return null;
}
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// --- Input -----------------------------------------------------------------
$rid = (int)($_GET['rid'] ?? 0);
$t   = $_GET['t'] ?? '';                 // token de cliente (puede venir vacío si es admin)
$ADMIN_KEY = admin_key();                // clave válida si es admin (puede venir de cookie)
$admin = $ADMIN_KEY !== null;

if(!$rid){
    http_response_code(400); echo "Missing reservation id"; exit;
}

// --- Carga reserva ----------------------------------------------------------
if ($admin) {
    // admin no necesita token
    $st = $pdo->prepare("
      SELECT r.*, c.name AS camper, c.price_per_night
      FROM reservations r
      JOIN campers c ON c.id = r.camper_id
      WHERE r.id = ? LIMIT 1
    ");
    $st->execute([$rid]);
} else {
    // cliente necesita token válido
    $st = $pdo->prepare("
      SELECT r.*, c.name AS camper, c.price_per_night
      FROM reservations r
      JOIN campers c ON c.id = r.camper_id
      WHERE r.id = ? AND r.manage_token = ? LIMIT 1
    ");
    $st->execute([$rid, $t]);
}

$r = $st->fetch(PDO::FETCH_ASSOC);
if(!$r){
    http_response_code(403);
    echo $admin ? "Reservation not found" : "Invalid link";
    exit;
}

// --- Datos útiles -----------------------------------------------------------
$nights = (int)((new DateTime($r['start_date']))->diff(new DateTime($r['end_date']))->format('%a'));
$price  = (float)$r['price_per_night'];
$deposit = $price; // tu política: depósito = 1 noche

// --- Cancelación ------------------------------------------------------------
$msgTop = '';
$refundNote = '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel'){
    // si ya estaba cancelada, no repitas
    if (strpos((string)$r['status'],'cancelled') === 0) {
        $qs = 'rid='.$rid.($admin ? '&key='.urlencode($ADMIN_KEY) : ($t ? '&t='.urlencode($t) : ''));
        header('Location: manage.php?'.$qs.'&m=already'); exit;
    }

    $newStatus = $admin ? 'cancelled_by_admin' : 'cancelled_by_customer';

    if ($admin && !empty($r['stripe_payment_intent'])) {
        // Admin: reembolsa (intenta evitar doble reembolso)
        try {
            $pi = PaymentIntent::retrieve($r['stripe_payment_intent']);
            $alreadyRefunded = false;
            if ($pi && isset($pi->charges->data[0])) {
                $ch = $pi->charges->data[0];
                $amountCaptured  = (int)($ch->amount_captured ?? 0);
                $amountRefunded  = (int)($ch->amount_refunded ?? 0);
                if ($amountRefunded >= $amountCaptured && $amountCaptured > 0) {
                    $alreadyRefunded = true;
                }
            }
            if (!$alreadyRefunded) {
                Refund::create(['payment_intent' => $r['stripe_payment_intent']]); // full refund
                $refundNote = 'Refund issued for the deposit.';
            } else {
                $refundNote = 'Charge has already been refunded.';
            }
        } catch (Throwable $e) {
            // No bloquees la cancelación si falla el reembolso; deja nota
            $refundNote = 'Refund error: '.$e->getMessage();
        }
    } else {
        // Cliente: sin reembolso por política
        $refundNote = 'Per policy, the deposit is non-refundable on customer-initiated cancellations.';
    }

    // Actualiza estado
    try {
        $up = $pdo->prepare("UPDATE reservations SET status=?, cancelled_at=NOW() WHERE id=? LIMIT 1");
        $up->execute([$newStatus, $rid]);
    } catch (Throwable $e) {
        $up = $pdo->prepare("UPDATE reservations SET status='cancelled', cancelled_at=NOW() WHERE id=? LIMIT 1");
        $up->execute([$rid]);
    }

    $qs = 'rid='.$rid.($admin ? '&key='.urlencode($ADMIN_KEY) : ($t ? '&t='.urlencode($t) : ''));
    if ($refundNote) $qs .= '&rn='.urlencode($refundNote);
    header('Location: manage.php?'.$qs.'&m=cancelled');
    exit;
}

// Mensajes de la vista
if (isset($_GET['m']) && $_GET['m']==='cancelled') {
    $msgTop = 'Reservation cancelled.';
} elseif (isset($_GET['m']) && $_GET['m']==='already') {
    $msgTop = 'This reservation was already cancelled.';
}
if (isset($_GET['rn'])) {
    $refundNote = (string)$_GET['rn'];
}

// --- Presentación -----------------------------------------------------------
$startHuman = date('j M Y', strtotime($r['start_date']));
$endHuman   = date('j M Y', strtotime($r['end_date']));
?>
<!doctype html>
<html lang="en"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reservation #<?= (int)$r['id'] ?> | Manage</title>

    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        .page-hero{ background-image:url('img/landing-matcha.02.31.jpeg'); }
        .wrap{ max-width: 1000px; margin-inline:auto; padding: var(--spacing-l); }
        .cardy{ background: var(--color-blanco); border-radius: var(--border-radius);
            box-shadow: var(--box-shadow-medium); border: 1px solid rgba(0,0,0,.04);
            padding: clamp(1rem, 2vw, 1.5rem); }
        .summary{ background: rgba(255,255,255,.6); border: 1px solid rgba(0,0,0,.06);
            border-radius: 12px; padding: 12px 14px; }
        .rowx{ display:flex; justify-content:space-between; padding: 8px 0; }
        .rowx + .rowx{ border-top: 1px dashed rgba(0,0,0,.08); }
        .total{ font-weight:700; }
        .badge-soft{ background: rgba(128,193,208,.15); color: var(--text-principal);
            border-radius: 999px; padding: .35rem .75rem; font-weight: 600; }
    </style>
</head>
<body>
<section class="page-hero">
    <div class="page-hero__content">
        <h1 class="page-hero__title">Reservation #<?= (int)$r['id'] ?></h1>
        <p class="mt-2"><?= $admin ? 'Admin' : 'Customer' ?> · Manage or cancel your trip</p>
    </div>
</section>

<main class="wrap">
    <?php if ($msgTop): ?>
        <div class="alert alert-success"><?= h($msgTop) ?></div>
    <?php endif; ?>
    <?php if ($refundNote): ?>
        <div class="alert alert-info small mb-3"><?= h($refundNote) ?></div>
    <?php endif; ?>

    <div class="cardy mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div class="flex-grow-1">
                <h2 class="h5 mb-2">Your trip</h2>
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <span class="badge-soft"><i class="bi bi-truck"></i> <?= h($r['camper']) ?></span>
                    <span class="badge-soft"><i class="bi bi-moon-stars"></i> <?= (int)$nights ?> night<?= $nights>1?'s':'' ?></span>
                </div>
                <p class="mb-1"><strong>Dates:</strong>
                    <span style="white-space:nowrap"><?= h($startHuman) ?>&nbsp;→&nbsp;<?= h($endHuman) ?></span>
                </p>
                <p class="mb-2"><strong>Status:</strong> <?= h($r['status']) ?></p>

                <?php if (strpos((string)$r['status'],'cancelled') !== 0): ?>
                    <form method="post" onsubmit="return confirm('Are you sure you want to cancel this reservation?');">
                        <input type="hidden" name="action" value="cancel">
                        <?php if ($admin): ?>
                            <p class="text-muted">Admin cancellation: the deposit will be refunded automatically.</p>
                        <?php else: ?>
                            <p class="text-warning">Customer cancellation: the deposit is non-refundable per policy.</p>
                        <?php endif; ?>
                        <button class="btn <?= $admin ? 'btn-danger' : 'btn-outline-secondary' ?>">
                            <i class="bi bi-x-circle"></i> Cancel reservation
                        </button>
                    </form>
                <?php else: ?>
                    <p class="text-muted">Cancelled at: <?= h($r['cancelled_at'] ?? '') ?></p>
                <?php endif; ?>
            </div>

            <div style="min-width:260px;max-width:320px;" class="ms-auto">
                <h3 class="h6 mb-2">Payment</h3>
                <div class="summary">
                    <div class="rowx"><span>Price/night</span><span>€<?= number_format($price, 2) ?></span></div>
                    <div class="rowx"><span>Nights</span><span><?= (int)$nights ?></span></div>
                    <div class="rowx total"><span>Deposit paid</span><span>€<?= number_format($deposit, 2) ?></span></div>
                </div>
            </div>
        </div>
    </div>

    <p class="text-muted small">
        Questions? Email us at <a href="mailto:alisios.van@gmail.com">alisios.van@gmail.com</a>.
    </p>
</main>
</body>
</html>
