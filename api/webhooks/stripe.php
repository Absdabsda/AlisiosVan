<?php
declare(strict_types=1);
ini_set('display_errors','0'); header('Content-Type: application/json');

require __DIR__ . '/../../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__ . '/../..')->safeLoad();
require __DIR__ . '/../../config/db.php';

use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Checkout\Session as StripeSession;
use Stripe\PaymentIntent;
use Stripe\Customer;

$pdo = get_pdo();
$sk  = $_ENV['STRIPE_SECRET'] ?? '';
$wh  = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';
if (!$sk || !$wh) { http_response_code(500); echo json_encode(['ok'=>false,'err'=>'env']); exit; }
Stripe::setApiKey($sk);

function norm($s){ return mb_strtolower(trim((string)$s)); }
function split_name(?string $full): array {
    $full = trim((string)$full);
    if ($full==='') return ['',''];
    $parts = preg_split('/\s+/u',$full);
    $first = array_shift($parts) ?? '';
    $last  = $parts ? implode(' ',$parts) : '';
    return [$first,$last];
}

$payload = file_get_contents('php://input');
$sig     = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try { $event = Webhook::constructEvent($payload, $sig, $wh); }
catch(Throwable){ http_response_code(400); echo 'Invalid signature'; exit; }

try{
    if ($event->type !== 'checkout.session.completed') {
        http_response_code(200); echo json_encode(['ok'=>true,'ignored'=>$event->type]); exit;
    }

    // Recupera sesión con expansiones
    /** @var \Stripe\Checkout\Session $session */
    $session = StripeSession::retrieve($event->data->object->id, ['expand'=>['payment_intent','customer']]);

    // 1) Localiza la reserva
    $reservationId = (int)($session->client_reference_id ?? ($session->metadata['reservation_id'] ?? 0));
    if ($reservationId===0) {
        $q = $pdo->prepare("SELECT id FROM reservations WHERE stripe_session_id=? LIMIT 1");
        $q->execute([$session->id]);
        if ($row=$q->fetch(PDO::FETCH_ASSOC)) $reservationId=(int)$row['id'];
    }
    if ($reservationId===0) { http_response_code(200); echo json_encode(['ok'=>true,'note'=>'no reservation']); exit; }

    // 2) Saca email del comprador (varias fuentes)
    $cands = [];
    if (!empty($session->customer_details->email))                  $cands[]=$session->customer_details->email;
    if (!empty($session->customer) && !empty($session->customer->email)) $cands[]=$session->customer->email;
    $pi = $session->payment_intent ? (is_string($session->payment_intent) ? PaymentIntent::retrieve($session->payment_intent) : $session->payment_intent) : null;
    if ($pi) {
        if (!empty($pi->receipt_email))                                     $cands[]=$pi->receipt_email;
        if (!empty($pi->charges->data[0]->billing_details->email))          $cands[]=$pi->charges->data[0]->billing_details->email;
        if (!empty($pi->customer)) {
            $cust = is_string($pi->customer) ? Customer::retrieve($pi->customer) : $pi->customer;
            if (!empty($cust->email))                                         $cands[]=$cust->email;
        }
    }
    $email = '';
    foreach ($cands as $e) if (filter_var($e,FILTER_VALIDATE_EMAIL)) { $email = norm($e); break; }
    $stripeCustomerId = is_string($session->customer) ? $session->customer : ($session->customer->id ?? null);

    // 3) UPSERT customer y enlaza la reserva
    $customerId = null;
    if ($email!=='') {
        $sel = $pdo->prepare("SELECT id, stripe_customer_id FROM customers WHERE email_norm=? LIMIT 1");
        $sel->execute([$email]);
        if ($c=$sel->fetch(PDO::FETCH_ASSOC)) {
            $customerId = (int)$c['id'];
            if ($stripeCustomerId && empty($c['stripe_customer_id'])) {
                $pdo->prepare("UPDATE customers SET stripe_customer_id=? WHERE id=? LIMIT 1")
                    ->execute([$stripeCustomerId,$customerId]);
            }
        } else {
            [$first,$last] = split_name($session->customer_details->name ?? '');
            $pdo->prepare("INSERT INTO customers (first_name,last_name,email,phone,email_norm,stripe_customer_id,created_at)
                     VALUES (?,?,?,?,?,?,NOW())")
                ->execute([$first,$last,$email,null,$email,$stripeCustomerId]);
            $customerId = (int)$pdo->lastInsertId();
        }
    }

    // 4) Actualiza reserva
    $status = ($session->payment_status ?? '') === 'paid' ? 'confirmed' : 'pending';
    $pdo->prepare("UPDATE reservations
                    SET status = IF(status='pending', ?, status),
                        stripe_payment_intent = COALESCE(?, stripe_payment_intent),
                        stripe_session_id     = COALESCE(?, stripe_session_id),
                        customer_id           = COALESCE(?, customer_id),
                        updated_at            = NOW()
                  WHERE id=? LIMIT 1")
        ->execute([$status, $pi?->id ?? null, $session->id, $customerId, $reservationId]);

    http_response_code(200);
    echo json_encode(['ok'=>true]);
} catch(Throwable $e){
    // respondemos 200 para no forzar reintentos infinitos mientras depuras
    http_response_code(200); echo json_encode(['ok'=>false,'err'=>$e->getMessage()]);
}
