<?php
declare(strict_types=1);
ini_set('display_errors','0');
header('Content-Type: application/json');

// 1) Carga común y DB
require_once __DIR__ . '/../config/bootstrap_env.php';
require __DIR__ . '/../../config/db.php';
$pdo = get_pdo();

use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Checkout\Session as StripeSession;
use Stripe\PaymentIntent;
use Stripe\Customer;

// 2) Claves desde /secure/.env
$sk = env('STRIPE_SECRET','');
$wh = env('STRIPE_WEBHOOK_SECRET','');
if (!$sk || !$wh) { http_response_code(500); echo json_encode(['ok'=>false,'err'=>'env']); exit; }
Stripe::setApiKey($sk);

// Utils
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

try {
    $event = Webhook::constructEvent($payload, $sig, $wh);
} catch (Throwable $e) {
    error_log('Stripe webhook signature error: '.$e->getMessage());
    http_response_code(400); echo json_encode(['ok'=>false,'err'=>'sig']); exit;
}

// Solo nos interesa checkout.session.completed (puedes añadir invoice.paid si quieres)
if ($event->type !== 'checkout.session.completed') {
    http_response_code(200); echo json_encode(['ok'=>true,'ignored'=>$event->type]); exit;
}

try {
    /** @var \Stripe\Checkout\Session $session */
    $session = StripeSession::retrieve($event->data->object->id, [
        'expand'=>['payment_intent','customer']
    ]);

    // 1) Localiza la reserva (por client_reference_id, metadata o session id)
    $reservationId = (int)($session->client_reference_id ?? ($session->metadata['reservation_id'] ?? 0));
    if ($reservationId === 0) {
        $q = $pdo->prepare("SELECT id FROM reservations WHERE stripe_session_id=? LIMIT 1");
        $q->execute([$session->id]);
        if ($row=$q->fetch(PDO::FETCH_ASSOC)) $reservationId = (int)$row['id'];
    }
    if ($reservationId === 0) {
        error_log('Webhook: no reservation found for session '.$session->id);
        http_response_code(200); echo json_encode(['ok'=>true,'note'=>'no reservation']); exit;
    }

    // 2) Determina email del comprador (varias fuentes)
    $cands = [];
    if (!empty($session->customer_details->email)) $cands[] = $session->customer_details->email;
    if (!empty($session->customer) && !empty($session->customer->email)) $cands[] = $session->customer->email;

    $pi = $session->payment_intent;
    if ($pi && is_string($pi)) $pi = PaymentIntent::retrieve($pi);
    if ($pi) {
        if (!empty($pi->receipt_email)) $cands[] = $pi->receipt_email;
        if (!empty($pi->charges->data[0]->billing_details->email)) $cands[] = $pi->charges->data[0]->billing_details->email;
        if (!empty($pi->customer)) {
            $cust = is_string($pi->customer) ? Customer::retrieve($pi->customer) : $pi->customer;
            if (!empty($cust->email)) $cands[] = $cust->email;
        }
    }

    $email = '';
    foreach ($cands as $e) if (filter_var($e,FILTER_VALIDATE_EMAIL)) { $email = norm($e); break; }
    $stripeCustomerId = is_string($session->customer) ? $session->customer : ($session->customer->id ?? null);

    // 3) UPSERT customers
    $customerId = null;
    if ($email !== '') {
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

    // 4) Actualiza la reserva (enlaza customer y guarda ids de Stripe)
    $status = ($session->payment_status ?? '') === 'paid' ? 'paid' : 'pending';
    $piId   = $pi?->id ?? null;

    $pdo->prepare("
    UPDATE reservations
       SET status               = IF(status='pending', ?, status),
           stripe_payment_intent= COALESCE(?, stripe_payment_intent),
           stripe_session_id    = COALESCE(?, stripe_session_id),
           customer_id          = COALESCE(?, customer_id),
           updated_at           = NOW()
     WHERE id=? LIMIT 1
  ")->execute([$status, $piId, $session->id, $customerId, $reservationId]);

    // 5) Genera manage_token si aún no hay
    $pdo->prepare("
    UPDATE reservations
       SET manage_token = COALESCE(manage_token, :tok)
     WHERE id = :rid
     LIMIT 1
  ")->execute([
        ':tok' => bin2hex(random_bytes(16)),
        ':rid' => $reservationId,
    ]);

    // 6) paid_at si procede
    if (($session->payment_status ?? '') === 'paid') {
        $pdo->prepare("
      UPDATE reservations
         SET paid_at = COALESCE(paid_at, NOW())
       WHERE id=? LIMIT 1
    ")->execute([$reservationId]);
    }

    http_response_code(200);
    echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
    error_log('Stripe webhook error: '.$e->getMessage());
    // Devolvemos 200 para evitar reintentos infinitos mientras depuras
    http_response_code(200); echo json_encode(['ok'=>false,'err'=>$e->getMessage()]);
}
