<?php
declare(strict_types=1);
ini_set('display_errors','0');

require_once __DIR__ . '/../../config/bootstrap_env.php';
require_once __DIR__ . '/../../config/db.php';

use Stripe\Stripe;
use Stripe\Checkout\Session as CheckoutSession;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$sk = env('STRIPE_SECRET','');
if (!$sk) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'stripe_not_configured']);
    exit;
}
Stripe::setApiKey($sk);

$sessionId = $_GET['session_id'] ?? '';
if (!$sessionId) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'missing_session_id']);
    exit;
}

try {
    // Traemos la sesión expandida (invoice + payment_intent)
    $session = CheckoutSession::retrieve($sessionId, [
        'expand'=>['invoice','payment_intent']
    ]);

    $invoiceId = null; $invoicePdf = null; $invoiceUrl = null; $status = null;

    if (!empty($session->invoice)) {
        // Puede venir ya expandida o como id
        if (is_object($session->invoice)) {
            $inv = $session->invoice;
        } else {
            $inv = \Stripe\Invoice::retrieve($session->invoice);
        }
        $invoiceId  = $inv->id ?? null;
        $invoicePdf = $inv->invoice_pdf ?? null;
        $invoiceUrl = $inv->hosted_invoice_url ?? null;
        $status     = $inv->status ?? null; // draft|open|paid|uncollectible|void
    }

    echo json_encode([
        'ok'          => true,
        'invoice_id'  => $invoiceId,
        'invoice_pdf' => $invoicePdf,
        'invoice_url' => $invoiceUrl,
        'status'      => $status,
    ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    // Mientras no esté lista, respondemos “not_ready”
    http_response_code(200);
    echo json_encode(['ok'=>false,'error'=>'not_ready']);
}
