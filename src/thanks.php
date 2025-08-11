<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../env'); $dotenv->safeLoad();
require __DIR__ . '/../config/db.php';
$pdo = get_pdo();

use Stripe\Stripe;
use Stripe\Checkout\Session as CheckoutSession;

$sessionId = $_GET['session_id'] ?? '';
if (!$sessionId) {
    http_response_code(400);
    echo 'Missing session_id';
    exit;
}

try {
    Stripe::setApiKey($_ENV['STRIPE_SECRET']);

    // Recupera la sesión (y el intent) desde Stripe
    $session = CheckoutSession::retrieve($sessionId, ['expand' => ['payment_intent']]);

    if ($session->payment_status === 'paid') {
        // Marca la reserva como pagada
        $up = $pdo->prepare("
            UPDATE reservations
               SET status='paid',
                   stripe_payment_intent=:pi,
                   paid_at=NOW()
             WHERE stripe_session_id=:ssid
             LIMIT 1
        ");
        $up->execute([
            ':pi'   => (string)$session->payment_intent,
            ':ssid' => $sessionId
        ]);

        // Saca datos para mostrar al usuario
        // ... tras hacer el UPDATE a 'paid' y antes de pintar HTML
        $st = $pdo->prepare("
    SELECT r.id, r.start_date, r.end_date,
           c.name AS camper,
           c.price_per_night,
           DATEDIFF(r.end_date, r.start_date) AS nights
      FROM reservations r
      JOIN campers c ON c.id = r.camper_id
     WHERE r.stripe_session_id = :ssid
     LIMIT 1
");
        $st->execute([':ssid' => $sessionId]);
        $res = $st->fetch(PDO::FETCH_ASSOC);
        if (!$res) { throw new Exception('Reservation not found after payment.'); }

        $nights = (int)$res['nights'];
        $price  = (float)$res['price_per_night'];
        $total  = $nights * $price;


        // Página de gracias
        ?>
        <!doctype html>
        <html lang="en">
        <head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Thanks | Alisios Van</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet"></head>
        <body class="bg-light">
        <div class="container py-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h3 mb-3">Payment confirmed</h1>
                    <p>Reservation #<?= (int)$res['id'] ?></p>
                    <ul>
                        <li>Camper: <?= htmlspecialchars($res['camper'], ENT_QUOTES, 'UTF-8') ?></li>
                        <li>From: <?= htmlspecialchars($res['start_date'], ENT_QUOTES, 'UTF-8') ?></li>
                        <li>To: <?= htmlspecialchars($res['end_date'], ENT_QUOTES, 'UTF-8') ?></li>
                        <li>Nights: <?= (int)$nights ?></li>
                        <li>Price/night: €<?= number_format((float)$price, 2) ?></li>
                        <li><strong>Total: €<?= number_format((float)$total, 2) ?></strong></li>
                    </ul>
                    <a class="btn btn-primary" href="index.php">Back to home</a>
                </div>
            </div>
        </div>
        </body></html>
        <!--hola-->
        <?php
        exit;
    }

    // Si no está pagada, informa:
    http_response_code(200);
    echo "Payment not completed yet. Status: " . htmlspecialchars($session->payment_status);

} catch (Throwable $e) {
    http_response_code(500);
    echo "Error: " . htmlspecialchars($e->getMessage());
}
