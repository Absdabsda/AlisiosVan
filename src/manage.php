<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__.'/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__.'/../env')->safeLoad();
require __DIR__.'/../config/db.php';
$pdo = get_pdo();

use Stripe\Stripe;
use Stripe\Refund;

Stripe::setApiKey($_ENV['STRIPE_SECRET']);

$rid = (int)($_GET['rid'] ?? 0);
$t   = $_GET['t'] ?? '';

if(!$rid || !$t){
    http_response_code(400); echo "Missing parameters"; exit;
}

// Carga reserva + vehículo
$st = $pdo->prepare("
  SELECT r.*, c.name AS camper
  FROM reservations r
  JOIN campers c ON c.id = r.camper_id
  WHERE r.id = ? LIMIT 1
");
$st->execute([$rid]);
$r = $st->fetch(PDO::FETCH_ASSOC);
if(!$r || !hash_equals($r['manage_token'], $t)){
    http_response_code(403); echo "Invalid link"; exit;
}

// Política simple de cancelación (ajusta a tu gusto)
const REFUND_FULL_BEFORE_DAYS = 7; // >=7 días antes → reembolso depósito
$now = new DateTime('now');
$start = new DateTime($r['start_date']);
$daysBefore = (int)$now->diff($start)->format('%r%a'); // días hasta inicio

// Cancelación
if($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel'){
    if($r['status'] === 'cancelled_by_customer'){
        header('Location: manage.php?rid='.$rid.'&t='.$t.'&m=already'); exit;
    }

    // Decide si reembolsar
    $shouldRefund = ($daysBefore >= REFUND_FULL_BEFORE_DAYS);
    $refundError = null;

    if($shouldRefund && !empty($r['stripe_payment_intent'])){
        try{
            // Reembolsa TODO lo cobrado (depósito)
            Refund::create(['payment_intent' => $r['stripe_payment_intent']]);
        }catch(Throwable $e){
            $refundError = $e->getMessage();
        }
    }

    // Marca cancelación
    $up = $pdo->prepare("
    UPDATE reservations
      SET status='cancelled_by_customer',
          cancelled_at=NOW()
    WHERE id=? LIMIT 1
  ");
    $up->execute([$rid]);

    // Opcional: notifica por correo a admin/cliente (puedes reutilizar tu PHPMailer)
    // ...

    $qs = 'rid='.$rid.'&t='.$t.'&m=cancelled'.($refundError ? '&re='.$refundError : '');
    header('Location: manage.php?'.$qs); exit;
}
?>
<!doctype html>
<html lang="en"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage reservation #<?= (int)$r['id'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="p-3">
<div class="container" style="max-width:800px">
    <h1 class="h4 mb-3">Reservation #<?= (int)$r['id'] ?></h1>
    <?php if(isset($_GET['m']) && $_GET['m']==='cancelled'): ?>
        <div class="alert alert-success">Reservation cancelled. If eligible, your deposit will be refunded.</div>
    <?php elseif(isset($_GET['m']) && $_GET['m']==='already'): ?>
        <div class="alert alert-info">This reservation is already cancelled.</div>
    <?php endif; ?>

    <div class="card p-3 mb-3">
        <div><strong>Camper:</strong> <?= htmlspecialchars($r['camper']) ?></div>
        <div><strong>Dates:</strong> <?= htmlspecialchars($r['start_date']) ?> → <?= htmlspecialchars($r['end_date']) ?></div>
        <div><strong>Status:</strong> <?= htmlspecialchars($r['status']) ?></div>
    </div>

    <?php if($r['status'] !== 'cancelled_by_customer'): ?>
        <form method="post" onsubmit="return confirm('Are you sure you want to cancel this reservation?');">
            <input type="hidden" name="action" value="cancel">
            <?php if($daysBefore >= REFUND_FULL_BEFORE_DAYS): ?>
                <p>Cancellation is eligible for a full refund of the deposit.</p>
            <?php else: ?>
                <p class="text-warning">Cancelling now may not be refundable according to our policy.</p>
            <?php endif; ?>
            <button class="btn btn-danger">Cancel reservation</button>
        </form>
    <?php endif; ?>
</div>
</body></html>
