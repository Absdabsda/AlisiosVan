<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__.'/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__.'/../env')->safeLoad();
require __DIR__.'/../config/db.php';

use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Stripe\PaymentIntent;
use Stripe\Customer;

$pdo = get_pdo();
$sk = $_ENV['STRIPE_SECRET'] ?? '';
if ($sk === '') { exit("Falta STRIPE_SECRET en .env\n"); }
Stripe::setApiKey($sk);

function norm($s){ return mb_strtolower(trim((string)$s)); }

echo "=== Backfill desde Stripe ===\n";

// Toma reservas que tienen session de Stripe (las que no tengan, se saltan)
$st = $pdo->query("
  SELECT id, customer_id, stripe_session_id, manage_token
  FROM reservations
  WHERE stripe_session_id IS NOT NULL AND stripe_session_id <> ''
");
$res = $st->fetchAll(PDO::FETCH_ASSOC);

foreach ($res as $r) {
    $rid  = (int)$r['id'];
    $ssid = (string)$r['stripe_session_id'];

    echo "RID $rid ... ";

    try {
        $sess = StripeSession::retrieve($ssid, ['expand'=>['payment_intent','customer']]);

        // candidatos de email
        $cands = [];
        if (!empty($sess->customer_details->email)) $cands[] = $sess->customer_details->email;
        if (!empty($sess->customer) && !empty($sess->customer->email)) $cands[] = $sess->customer->email;

        $pi = null;
        if (!empty($sess->payment_intent)) {
            $pi = is_string($sess->payment_intent) ? PaymentIntent::retrieve($sess->payment_intent) : $sess->payment_intent;
            if (!empty($pi->receipt_email)) $cands[] = $pi->receipt_email;
            if (!empty($pi->charges->data[0]->billing_details->email)) $cands[] = $pi->charges->data[0]->billing_details->email;
            if (!empty($pi->customer)) {
                $cust = is_string($pi->customer) ? Customer::retrieve($pi->customer) : $pi->customer;
                if (!empty($cust->email)) $cands[] = $cust->email;
            }
        }

        // elige primer email válido
        $email = '';
        foreach ($cands as $e) {
            if (filter_var($e, FILTER_VALIDATE_EMAIL)) { $email = norm($e); break; }
        }
        if (!$email) { echo "sin email en Stripe\n"; continue; }

        // intenta obtener stripe_customer_id
        $stripeCustomerId = null;
        if (!empty($sess->customer)) {
            $stripeCustomerId = is_string($sess->customer) ? $sess->customer : $sess->customer->id;
        } elseif ($pi && !empty($pi->customer)) {
            $stripeCustomerId = is_string($pi->customer) ? $pi->customer : $pi->customer->id;
        }

        // UPSERT del customer por email_norm
        $sel = $pdo->prepare("SELECT id, stripe_customer_id FROM customers WHERE email_norm = ? LIMIT 1");
        $sel->execute([$email]);
        $c = $sel->fetch(PDO::FETCH_ASSOC);

        if ($c) {
            $customerId = (int)$c['id'];
            if ($stripeCustomerId && empty($c['stripe_customer_id'])) {
                $upd = $pdo->prepare("UPDATE customers SET stripe_customer_id=? WHERE id=? LIMIT 1");
                $upd->execute([$stripeCustomerId, $customerId]);
            }
        } else {
            $ins = $pdo->prepare("
        INSERT INTO customers (first_name,last_name,email,phone,created_at, email_norm, stripe_customer_id)
        VALUES ('','',?,NULL,NOW(), ?, ?)
      ");
            $ins->execute([$email, $email, $stripeCustomerId]);
            $customerId = (int)$pdo->lastInsertId();
        }

        // enlaza reservation → customer
        $upd = $pdo->prepare("UPDATE reservations SET customer_id=? WHERE id=? LIMIT 1");
        $upd->execute([$customerId, $rid]);

        // asegura manage_token
        if (empty($r['manage_token'])) {
            $tok = bin2hex(random_bytes(16));
            $pdo->prepare("UPDATE reservations SET manage_token=? WHERE id=? LIMIT 1")->execute([$tok, $rid]);
        }

        echo "OK (email=$email, customer_id=$customerId)\n";
    } catch (Throwable $e) {
        echo "ERROR: ".$e->getMessage()."\n";
        continue;
    }
}

echo "Hecho.\n";
