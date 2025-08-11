<?php
declare(strict_types=1);
ini_set('display_errors', '1'); error_reporting(E_ALL);

require __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../env');
$dotenv->safeLoad();

require __DIR__ . '/../../config/db.php';
$pdo = get_pdo();

$endpointSecret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';
$payload = @file_get_contents('php://input');
$sig = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    if (!$endpointSecret) throw new Exception('Falta STRIPE_WEBHOOK_SECRET');

    $event = \Stripe\Webhook::constructEvent($payload, $sig, $endpointSecret);

    switch ($event->type) {
        case 'checkout.session.completed':
            $session = $event->data->object;

            $reservationId = (int)($session->client_reference_id ?? ($session->metadata['reservation_id'] ?? 0));
            $paymentIntent = $session->payment_intent ?? null;

            if ($reservationId) {
                $st = $pdo->prepare("
          UPDATE reservations
             SET status='paid',
                 stripe_payment_intent = ?,
                 stripe_session_id = ?,
                 updated_at = NOW()
           WHERE id = ?
        ");
                $st->execute([$paymentIntent, $session->id, $reservationId]);
            }
            break;

        // Puedes manejar más eventos si quieres:
        // case 'payment_intent.payment_failed':
        //   ...
        //   break;
    }

    http_response_code(200);
    echo json_encode(['received' => true]);
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    echo 'Invalid signature';
} catch (Throwable $e) {
    http_response_code(400);
    echo 'Error: ' . $e->getMessage();
}
