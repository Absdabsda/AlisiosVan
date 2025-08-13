<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__ . '/../env')->safeLoad();
require __DIR__ . '/../config/db.php';

use Stripe\StripeClient;

$pdo = get_pdo();


$rid = isset($_GET['rid']) ? (int)$_GET['rid'] : 0;
if ($rid < 1) {
    http_response_code(400);
    echo 'Missing or invalid reservation id.';
    exit;
}

try {
    //Recupera la reserva
    $st = $pdo->prepare("SELECT id, status, stripe_session_id FROM reservations WHERE id = :id LIMIT 1");
    $st->execute([':id' => $rid]);
    $res = $st->fetch(PDO::FETCH_ASSOC);

    if (!$res) {
        http_response_code(404);
        echo 'Reservation not found.';
        exit;
    }

    $status = (string)$res['status'];
    $sessionId = $res['stripe_session_id'] ? (string)$res['stripe_session_id'] : '';

    $changed = false;
    $message = '';

    //Si está 'pending', la cancelamos
    if ($status === 'pending') {
        $up = $pdo->prepare("
            UPDATE reservations
               SET status='cancelled',
                   cancelled_at=NOW()
             WHERE id=:id AND status='pending'
             LIMIT 1
        ");
        $up->execute([':id' => $rid]);
        $changed = $up->rowCount() > 0;

        // Intenta expirar la Checkout Session
        if ($sessionId && !empty($_ENV['STRIPE_SECRET'])) {
            try {
                $stripe = new StripeClient($_ENV['STRIPE_SECRET']);
                // Si la sesión ya está completa o expirada, Stripe lanzará error;
                $stripe->checkout->sessions->expire($sessionId);
            } catch (Throwable $e) {
                // Log opcional
                error_log("No se pudo expirar la session $sessionId: " . $e->getMessage());
            }
        }

        $message = $changed
            ? 'Tu reserva ha sido cancelada correctamente.'
            : 'No se pudo cancelar la reserva (quizá cambió de estado).';
    } else {
        //Para otros estados, solo informamos

        $nonCancelable = ['paid', 'confirmed', 'confirmed_deposit', 'paid_deposit'];
        if (in_array($status, $nonCancelable, true)) {
            $message = 'Esta reserva ya está confirmada. Si deseas cancelarla, ponte en contacto con nosotros.';
        } elseif ($status === 'cancelled') {
            $message = 'Esta reserva ya estaba cancelada.';
        } else {
            $message = 'Esta reserva no puede cancelarse desde esta página.';
        }
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo "Error: " . htmlspecialchars($e->getMessage());
    exit;
}


?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reserva cancelada | Alisios Van</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-3">Estado de la reserva #<?= (int)$rid ?></h1>
            <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
            <a class="btn btn-primary" href="index.php">Volver al inicio</a>
        </div>
    </div>
</div>
</body>
</html>
