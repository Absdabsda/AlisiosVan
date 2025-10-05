<?php
declare(strict_types=1);
ini_set('display_errors','1');
error_reporting(E_ALL);

// Composer + .env desde /secure
require_once __DIR__ . '/../config/bootstrap_env.php';
require_once __DIR__ . '/../config/i18n-lite.php';   // i18n
require __DIR__.'/../config/db.php';
$pdo = get_pdo();

use Stripe\Stripe;
use Stripe\Refund;
use Stripe\PaymentIntent;
use Stripe\Checkout\Session as CheckoutSession;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

// Clave de Stripe desde /secure/.env
Stripe::setApiKey(env('STRIPE_SECRET',''));

/* -----------------------------------------------------------
 * I18N mínimo para la página de gestión
 * ---------------------------------------------------------*/
function current_locale(): string {
    $try = strtolower((string)($_GET['lang'] ?? $_GET['l'] ?? $_COOKIE['lang'] ?? ''));
    if (!$try && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $try = substr(strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']), 0, 2);
    }
    $supported = ['en','es','de','fr','it'];
    return in_array($try, $supported, true) ? $try : 'en';
}

$LC = current_locale();
if (function_exists('i18n_set_locale')) {
    i18n_set_locale($LC); // fija el idioma global para __()
}

/* ---- Strings UI locales (solo para los textos de la página) ---- */
$STRINGS = [
    'en' => [
        'error.missingRid'  => 'Missing reservation id',
        'error.notfound'    => 'Reservation not found',
        'error.invalidLink' => 'Invalid link',

        'page.title'        => 'Reservation #%d | Manage',
        'hero.title'        => 'Reservation #%d',
        'hero.subtitle.admin'    => 'Admin · Manage or cancel the trip',
        'hero.subtitle.customer' => 'Customer · Manage or cancel your trip',

        'alert.cancelled'   => 'Reservation cancelled.',
        'alert.already'     => 'This reservation was already cancelled.',

        'section.trip'      => 'Your trip',
        'label.dates'       => 'Dates',
        'label.status'      => 'Status',

        'badge.night.one'   => 'night',
        'badge.night.other' => 'nights',

        'confirm.cancel'    => 'Are you sure you want to cancel this reservation?',
        'note.admin'        => 'Admin cancellation: the deposit will be refunded automatically.',
        'note.customer'     => 'Customer cancellation: the deposit is non-refundable per policy.',
        'btn.cancel'        => 'Cancel reservation',
        'label.cancelledAt' => 'Cancelled at:',

        'section.payment'   => 'Payment',
        'label.priceNight'  => 'Price/night',
        'label.nights'      => 'Nights',
        'label.depositPaid' => 'Deposit paid',
        'label.rules'       => 'Rules',

        'footer.questions'  => 'Questions? Email us at',
    ],
    'es' => [
        'error.missingRid'  => 'Falta el id de la reserva',
        'error.notfound'    => 'Reserva no encontrada',
        'error.invalidLink' => 'Enlace no válido',

        'page.title'        => 'Reserva #%d | Gestionar',
        'hero.title'        => 'Reserva #%d',
        'hero.subtitle.admin'    => 'Admin · Gestionar o cancelar el viaje',
        'hero.subtitle.customer' => 'Cliente · Gestiona o cancela tu viaje',

        'alert.cancelled'   => 'Reserva cancelada.',
        'alert.already'     => 'Esta reserva ya estaba cancelada.',

        'section.trip'      => 'Tu viaje',
        'label.dates'       => 'Fechas',
        'label.status'      => 'Estado',

        'badge.night.one'   => 'noche',
        'badge.night.other' => 'noches',

        'confirm.cancel'    => '¿Seguro que quieres cancelar esta reserva?',
        'note.admin'        => 'Cancelación por admin: el depósito se reembolsará automáticamente.',
        'note.customer'     => 'Cancelación por cliente: el depósito no es reembolsable según la política.',
        'btn.cancel'        => 'Cancelar reserva',
        'label.cancelledAt' => 'Cancelada el:',

        'section.payment'   => 'Pago',
        'label.priceNight'  => 'Precio/noche',
        'label.nights'      => 'Noches',
        'label.depositPaid' => 'Depósito pagado',
        'label.rules'       => 'Reglas',

        'footer.questions'  => '¿Dudas? Escríbenos a',
    ],
    'de' => [
        'error.missingRid'  => 'Reservierungs-ID fehlt',
        'error.notfound'    => 'Reservierung nicht gefunden',
        'error.invalidLink' => 'Ungültiger Link',

        'page.title'        => 'Reservierung Nr. %d | Verwalten',
        'hero.title'        => 'Reservierung Nr. %d',
        'hero.subtitle.admin'    => 'Admin · Reise verwalten oder stornieren',
        'hero.subtitle.customer' => 'Kunde · Reise verwalten oder stornieren',

        'alert.cancelled'   => 'Reservierung storniert.',
        'alert.already'     => 'Diese Reservierung wurde bereits storniert.',

        'section.trip'      => 'Deine Reise',
        'label.dates'       => 'Zeitraum',
        'label.status'      => 'Status',

        'badge.night.one'   => 'Nacht',
        'badge.night.other' => 'Nächte',

        'confirm.cancel'    => 'Möchtest du diese Reservierung wirklich stornieren?',
        'note.admin'        => 'Admin-Stornierung: Die Anzahlung wird automatisch erstattet.',
        'note.customer'     => 'Kundenstornierung: Die Anzahlung ist gemäß Richtlinie nicht erstattungsfähig.',
        'btn.cancel'        => 'Reservierung stornieren',
        'label.cancelledAt' => 'Storniert am:',

        'section.payment'   => 'Zahlung',
        'label.priceNight'  => 'Preis/Nacht',
        'label.nights'      => 'Nights',
        'label.depositPaid' => 'Anzahlung bezahlt',
        'label.rules'       => 'Regeln',

        'footer.questions'  => 'Fragen? Schreib uns an',
    ],
    'fr' => [
        'error.missingRid'  => 'ID de réservation manquante',
        'error.notfound'    => 'Réservation introuvable',
        'error.invalidLink' => 'Lien invalide',

        'page.title'        => 'Réservation n° %d | Gérer',
        'hero.title'        => 'Réservation n° %d',
        'hero.subtitle.admin'    => 'Admin · Gérer ou annuler le voyage',
        'hero.subtitle.customer' => 'Client · Gérez ou annulez votre voyage',

        'alert.cancelled'   => 'Réservation annulée.',
        'alert.already'     => 'Cette réservation était déjà annulée.',

        'section.trip'      => 'Votre voyage',
        'label.dates'       => 'Dates',
        'label.status'      => 'Statut',

        'badge.night.one'   => 'nuit',
        'badge.night.other' => 'nuits',

        'confirm.cancel'    => 'Voulez-vous vraiment annuler cette réservation ?',
        'note.admin'        => 'Annulation par l’admin : l’acompte sera remboursé automatiquement.',
        'note.customer'     => 'Annulation par le client : l’acompte n’est pas remboursable selon la politique.',
        'btn.cancel'        => 'Annuler la réservation',
        'label.cancelledAt' => 'Annulée le :',

        'section.payment'   => 'Paiement',
        'label.priceNight'  => 'Prix/nuit',
        'label.nights'      => 'Nuits',
        'label.depositPaid' => 'Acompte payé',
        'label.rules'       => 'Règles',

        'footer.questions'  => 'Des questions ? Écrivez-nous à',
    ],
    'it' => [
        'error.missingRid'  => 'ID prenotazione mancante',
        'error.notfound'    => 'Prenotazione non trovata',
        'error.invalidLink' => 'Link non valido',

        'page.title'        => 'Prenotazione n. %d | Gestisci',
        'hero.title'        => 'Prenotazione n. %d',
        'hero.subtitle.admin'    => 'Admin · Gestisci o annulla il viaggio',
        'hero.subtitle.customer' => 'Cliente · Gestisci o annulla il tuo viaggio',

        'alert.cancelled'   => 'Prenotazione annullata.',
        'alert.already'     => 'Questa prenotazione era già stata annullata.',

        'section.trip'      => 'Il tuo viaggio',
        'label.dates'       => 'Date',
        'label.status'      => 'Stato',

        'badge.night.one'   => 'notte',
        'badge.night.other' => 'notti',

        'confirm.cancel'    => 'Sei sicuro di voler annullare questa prenotazione?',
        'note.admin'        => "Annullamento dall'admin: il deposito sarà rimborsato automaticamente.",
        'note.customer'     => "Annullamento del cliente: il deposito non è rimborsabile secondo la policy.",
        'btn.cancel'        => 'Annulla prenotazione',
        'label.cancelledAt' => 'Annullata il:',

        'section.payment'   => 'Pagamento',
        'label.priceNight'  => 'Prezzo/notte',
        'label.nights'      => 'Noches',
        'label.depositPaid' => 'Deposito pagato',
        'label.rules'       => 'Regole',

        'footer.questions'  => 'Domande? Scrivici a',
    ],
];

function t(string $key, ...$args): string {
    global $STRINGS, $LC;
    $base = $STRINGS[$LC][$key] ?? ($STRINGS['en'][$key] ?? $key);
    return $args ? vsprintf($base, $args) : $base;
}
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Traducción de estados con i18n global (usa tus claves status.cancelled_by_*)
function status_label(string $status): string {
    $k = 'status.' . $status;      // p.ej. status.cancelled_by_customer
    $txt = __($k);
    return ($txt !== $k) ? $txt : $status;
}

/* -----------------------------------------------------------
 * Helpers / DB / Stripe / Mail
 * ---------------------------------------------------------*/

// --- Admin por COOKIE (no aceptamos ?key en manage.php) ---
function is_admin_cookie(): bool {
    $envKey = env('ADMIN_KEY','');
    if (!$envKey) return false;
    $k = $_COOKIE['admin_key'] ?? '';
    return $k && hash_equals($envKey, (string)$k);
}
$adminCookie = is_admin_cookie();
// Solo es “actor admin” si vienes por manage-admin.php (define MANAGE_FORCE_ADMIN_UI)
$forceAdminUi = defined('MANAGE_FORCE_ADMIN_UI') && MANAGE_FORCE_ADMIN_UI === true;
$adminACT = ($adminCookie && $forceAdminUi);  // actor
$adminUI  = $forceAdminUi && $adminCookie;    // mostrar UI admin

function columnExists(PDO $pdo, string $table, string $column): bool {
    $sql = "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([':t'=>$table, ':c'=>$column]);
    return (bool)$st->fetchColumn();
}

/** Intenta obtener email y nombre del cliente */
function getReservationContact(PDO $pdo, array $r): array {
    try {
        if (columnExists($pdo, 'reservations', 'customer_email') && !empty($r['customer_email'])) {
            return ['email' => (string)$r['customer_email'], 'name' => (string)($r['customer_name'] ?? '')];
        }
        if (columnExists($pdo, 'reservations', 'email') && !empty($r['email'])) {
            return ['email' => (string)$r['email'], 'name' => (string)($r['name'] ?? '')];
        }
    } catch (\Throwable $e) { /* ignore */ }

    try {
        if (!empty($r['stripe_payment_intent'])) {
            $pi = PaymentIntent::retrieve($r['stripe_payment_intent']);
            if ($pi && isset($pi->charges->data[0])) {
                $ch = $pi->charges->data[0];
                $email = $ch->billing_details->email ?? $ch->receipt_email ?? null;
                $name  = $ch->billing_details->name ?? '';
                if ($email) return ['email'=>(string)$email, 'name'=>(string)$name];
            }
        }
    } catch (\Throwable $e) { /* ignore */ }

    try {
        if (!empty($r['stripe_session_id'])) {
            $sess = CheckoutSession::retrieve($r['stripe_session_id'], ['expand'=>['customer','customer_details','payment_intent']]);
            $email = $sess->customer_details->email ?? ($sess->customer->email ?? null);
            $name  = $sess->customer_details->name  ?? ($sess->customer->name  ?? '');
            if ($email) return ['email'=>(string)$email, 'name'=>(string)$name];
            if ($sess->payment_intent && isset($sess->payment_intent->charges->data[0])) {
                $ch = $sess->payment_intent->charges->data[0];
                $email = $ch->billing_details->email ?? $ch->receipt_email ?? null;
                $name  = $ch->billing_details->name ?? '';
                if ($email) return ['email'=>(string)$email, 'name'=>(string)$name];
            }
        }
    } catch (\Throwable $e) { /* ignore */ }

    return ['email'=>'', 'name'=>''];
}

/**
 * Si falta stripe_payment_intent, intenta resolverlo desde:
 * 1) checkout session
 * 2) invoice
 * 3) Stripe Search API — priorizando PIs en estado succeeded/requires_capture y con amount==deposit_cents
 */
function ensurePaymentIntentId(PDO $pdo, array &$r): ?string {
    try {
        if (!empty($r['stripe_payment_intent'])) {
            return (string)$r['stripe_payment_intent'];
        }
        // 1) Desde la sesión de checkout
        if (!empty($r['stripe_session_id'])) {
            $sess = CheckoutSession::retrieve($r['stripe_session_id'], ['expand'=>['payment_intent']]);
            $piId = $sess->payment_intent?->id ?? null;
            if ($piId) {
                $r['stripe_payment_intent'] = (string)$piId;
                if (columnExists($pdo, 'reservations', 'stripe_payment_intent')) {
                    $pdo->prepare("UPDATE reservations SET stripe_payment_intent = :pi WHERE id = :id LIMIT 1")
                        ->execute([':pi'=>$piId, ':id'=>(int)$r['id']]);
                }
                return (string)$piId;
            }
        }
        // 2) Desde la factura (si existe)
        if (!empty($r['stripe_invoice_id'])) {
            $inv = \Stripe\Invoice::retrieve($r['stripe_invoice_id']);
            $piId = is_object($inv->payment_intent) ? ($inv->payment_intent->id ?? null) : (string)($inv->payment_intent ?? '');
            if ($piId) {
                $r['stripe_payment_intent'] = (string)$piId;
                if (columnExists($pdo, 'reservations', 'stripe_payment_intent')) {
                    $pdo->prepare("UPDATE reservations SET stripe_payment_intent = :pi WHERE id = :id LIMIT 1")
                        ->execute([':pi'=>$piId, ':id'=>(int)$r['id']]);
                }
                return (string)$piId;
            }
        }
        // 3) Búsqueda por metadata (Stripe Search API) con preferencia
        try {
            $q = "metadata['reservation_id']:'" . (int)$r['id'] . "'";
            $res = \Stripe\PaymentIntent::search(['query' => $q, 'limit' => 10]);
            $target = null;
            $wantAmount = null;
            if (isset($r['deposit_cents']) && is_numeric($r['deposit_cents'])) {
                $wantAmount = (int)$r['deposit_cents'];
            }
            foreach ($res->data as $cand) {
                $st = $cand->status ?? '';
                $okStatus = in_array($st, ['succeeded','requires_capture'], true);
                $okAmount = $wantAmount ? ((int)$cand->amount === $wantAmount) : true;
                if ($okStatus && $okAmount) { $target = $cand; break; }
                if (!$target && $okStatus) { $target = $cand; }
            }
            if ($target) {
                $piId = (string)$target->id;
                $r['stripe_payment_intent'] = $piId;
                if (columnExists($pdo, 'reservations', 'stripe_payment_intent')) {
                    $pdo->prepare("UPDATE reservations SET stripe_payment_intent = :pi WHERE id = :id LIMIT 1")
                        ->execute([':pi'=>$piId, ':id'=>(int)$r['id']]);
                }
                return $piId;
            }
        } catch (\Throwable $e) {
            error_log('PI search fallback error: '.$e->getMessage());
        }
    } catch (\Throwable $e) {
        error_log('ensurePaymentIntentId error: '.$e->getMessage());
    }
    return null;
}


/** Crea PHPMailer configurado desde variables de entorno o devuelve null si falta config */
function buildMailerFromEnv(): ?PHPMailer {
    $host = env('SMTP_HOST', env('MAIL_HOST', ''));
    $port = (int)env('SMTP_PORT', env('MAIL_PORT', 587));
    $user = env('SMTP_USER', env('MAIL_USER', ''));
    $pass = env('SMTP_PASS', env('MAIL_PASS', ''));
    $secureRaw = strtolower((string)env('SMTP_SECURE','tls'));
    $fromEmail = env('SMTP_FROM', env('MAIL_FROM', $user));
    $fromName  = env('SMTP_FROM_NAME', env('MAIL_FROM_NAME', 'Alisios Van'));

    if (!$host || !$user || !$pass) return null;

    $mail = new PHPMailer(true);
    $mail->CharSet  = 'UTF-8';
    $mail->Encoding = 'quoted-printable';
    $mail->isSMTP();
    $mail->Host       = $host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $user;
    $mail->Password   = $pass;
    if ($secureRaw === 'ssl' || $port === 465) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = $port ?: 465;
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $port ?: 587;
    }
    $mail->setFrom($fromEmail ?: $user, $fromName);
    $mail->addReplyTo('alisios.van@gmail.com', 'Alisios Van');
    $mail->addCustomHeader('Content-Language', $GLOBALS['LANG'] ?? $LC);
    return $mail;
}

// --- Input -----------------------------------------------------------------
$rid = (int)($_GET['rid'] ?? 0);
$t   = $_GET['t'] ?? '';

if(!$rid){
    http_response_code(400); echo t('error.missingRid'); exit;
}

// --- Carga reserva ----------------------------------------------------------
if ($adminACT) {
    $st = $pdo->prepare("
      SELECT r.*, c.name AS camper, c.price_per_night
      FROM reservations r
      JOIN campers c ON c.id = r.camper_id
      WHERE r.id = ? LIMIT 1
    ");
    $st->execute([$rid]);
} else {
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
    echo $adminACT ? t('error.notfound') : t('error.invalidLink');
    exit;
}

// --- Datos útiles -----------------------------------------------------------
$nights = (int)((new DateTime($r['start_date']))->diff(new DateTime($r['end_date']))->format('%a'));

// Precio base (el de la tabla campers) y total base
$baseUnit   = (float)$r['price_per_night'];
$baseTotal  = $baseUnit * $nights;

// Precio/total con reglas (si la reserva tiene total_cents calculado en checkout con rules)
$totalCentsDb = (int)($r['total_cents'] ?? 0);
if ($totalCentsDb > 0) {
    $ruleTotal = $totalCentsDb / 100.0;
    $ruleUnit  = $nights > 0 ? ($ruleTotal / $nights) : $baseUnit;
} else {
    $ruleTotal = $baseTotal;
    $ruleUnit  = $baseUnit;
}

// Lo que se muestra como “precio actual”
$price = $ruleUnit;
$total = $ruleTotal;

// Usa SIEMPRE el depósito guardado (coherente con la sesión de checkout)
$depositCents = isset($r['deposit_cents']) ? (int)$r['deposit_cents'] : (int)round($ruleTotal * 0.20 * 100);
$deposit      = $depositCents / 100.0;

// --- Cancelación ------------------------------------------------------------
$msgTop = '';
$refundNote = '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel'){
    // 1) Ya estaba cancelada
    if (strpos((string)$r['status'],'cancelled') === 0) {
        $qs = 'rid='.$rid.'&lang='.$LC;
        if (!$adminACT && $t) { $qs .= '&t='.urlencode($t); }
        $target = $adminACT ? 'manage-admin.php' : 'manage.php';
        header('Location: '.$target.'?'.$qs.'&m=already');
        exit;
    }

    // Decide el nuevo estado según quién cancela
    $newStatus  = $adminACT ? 'cancelled_by_admin' : 'cancelled_by_customer';
    $refundNote = '';

    // === Reembolso/Anulación: solo ADMIN (versión robusta) ===
    $piId = $adminACT ? (ensurePaymentIntentId($pdo, $r) ?? $r['stripe_payment_intent'] ?? null) : null;

    if ($adminACT && $piId) {
        try {
            $pi = \Stripe\PaymentIntent::retrieve($piId, ['expand' => ['charges.data']]);
            $received = (int)($pi->amount_received ?? 0);
            $alreadyRefunded = 0;
            foreach (($pi->charges->data ?? []) as $ch) {
                $alreadyRefunded += (int)($ch->amount_refunded ?? 0);
            }
            $remaining = max(0, $received - $alreadyRefunded);

            $amountToRefund = min($depositCents, $remaining);
            if ($amountToRefund <= 0) {
                throw new \Exception('NO_REMAINING_TO_REFUND');
            }

            $refund = \Stripe\Refund::create(
                [
                    'payment_intent' => $piId,
                    'amount'         => $amountToRefund,
                    'metadata'       => [
                        'reservation_id' => (string)$r['id'],
                        'reason'         => 'admin_cancel',
                    ],
                ],
                ['idempotency_key' => 'res-'.$r['id'].'-cancel-refund-'.$amountToRefund]
            );

            $refundNote = 'Refund issued: €'.number_format($amountToRefund/100, 2).' ('.$refund->id.')';

            if (columnExists($pdo, 'reservations', 'stripe_refund_id')) {
                $pdo->prepare("UPDATE reservations SET stripe_refund_id = :rid WHERE id = :id LIMIT 1")
                    ->execute([':rid' => (string)$refund->id, ':id' => (int)$r['id']]);
            }

        } catch (\Stripe\Exception\ApiErrorException $e) {
            $code = $e->getError()->code ?? '';
            $msg  = $e->getMessage();

            if (in_array($code, ['charge_not_captured','payment_intent_unexpected_state'], true)) {
                try {
                    $pi2 = \Stripe\PaymentIntent::retrieve($piId);
                    if (($pi2->status ?? '') === 'requires_capture') {
                        \Stripe\PaymentIntent::cancel($piId);
                        $refundNote = 'Authorization voided (requires_capture → cancelled).';
                    } else {
                        $refundNote = 'No captured funds to refund.';
                    }
                } catch (\Throwable $e2) {
                    $refundNote = 'Void attempt failed: '.$e2->getMessage();
                }
            } else {
                $refundNote = 'Refund error: '.$msg;
            }

        } catch (\Throwable $e) {
            try {
                $pi3 = \Stripe\PaymentIntent::retrieve($piId);
                if (($pi3->status ?? '') === 'requires_capture') {
                    \Stripe\PaymentIntent::cancel($piId);
                    $refundNote = 'Authorization voided (requires_capture → cancelled).';
                } else {
                    $refundNote = 'No captured funds to refund.';
                }
            } catch (\Throwable $e3) {
                $refundNote = 'Refund/void error: '.$e3->getMessage();
            }
        }
    } elseif ($adminACT) {
        $refundNote = 'Skipping refund/void: missing PaymentIntent (rid='.(int)$r['id'].').';
    }

    // Actualiza estado
    try {
        $up = $pdo->prepare("UPDATE reservations SET status = :s, cancelled_at = NOW() WHERE id = :id LIMIT 1");
        $up->execute([':s' => $newStatus, ':id' => $rid]);
        $r['status']       = $newStatus;
        $r['cancelled_at'] = date('Y-m-d H:i:s');
    } catch (\Throwable $e) {
        $up = $pdo->prepare("UPDATE reservations SET status = 'cancelled', cancelled_at = NOW() WHERE id = :id LIMIT 1");
        $up->execute([':id' => $rid]);
        $r['status']       = 'cancelled';
        $r['cancelled_at'] = date('Y-m-d H:i:s');
    }

    // ======= Email de confirmación de cancelación =======
    try {
        $emailSentAlready = false;
        if (columnExists($pdo, 'reservations', 'cancellation_email_sent_at')) {
            $q = $pdo->prepare("SELECT cancellation_email_sent_at FROM reservations WHERE id=? LIMIT 1");
            $q->execute([$rid]);
            $emailSentAlready = (bool)$q->fetchColumn();
        }

        if (!$emailSentAlready) {
            $contact = getReservationContact($pdo, $r);
            $toEmail = $contact['email'] ?? '';
            $toName  = $contact['name']  ?? '';

            if ($toEmail) {
                $startHumanMail = date('j M Y', strtotime($r['start_date']));
                $endHumanMail   = date('j M Y', strtotime($r['end_date']));
                $nightsMail     = (int)((new DateTime($r['start_date']))->diff(new DateTime($r['end_date']))->format('%a'));

                $subject = sprintf(__('email.cancel.subject'), (int)$r['id']);
                $header  = __('email.cancel.header');
                $lead    = $adminACT ? __('email.cancel.lead.admin') : __('email.cancel.lead.customer');
                $summary = __('email.cancel.summary');
                $mistake = __('email.cancel.mistake');

                $labelCamper = __('label.camper') !== 'label.camper' ? __('label.camper') : 'Camper';
                $labelDates  = t('label.dates');
                $labelNights = t('label.nights');
                $labelStatus = t('label.status');

                $statusTxt = status_label($r['status']);

                $html = '
<div style="font-family:Arial,sans-serif;line-height:1.55;color:#333;background:#e8e6e4;padding:24px;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
    <tr><td align="center">
      <table role="presentation" width="640" cellspacing="0" cellpadding="0"
             style="background:#fff;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.10);overflow:hidden;border:1px solid rgba(0,0,0,0.04);">
        <tr>
          <td style="background:#80C1D0;color:#fff;padding:18px 22px;">
            <div style="font-size:22px;font-weight:700;">'.h($header).'</div>
            <div style="opacity:.9;margin-top:4px;">#'.(int)$r['id'].' — Alisios Van</div>
          </td>
        </tr>
        <tr>
          <td style="padding:20px 22px;">
            <p style="margin:0 0 10px 0">'.h($lead).'</p>
            <p style="margin:0 0 14px 0">'.h($summary).'</p>
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                   style="border:1px solid rgba(0,0,0,0.06); border-radius:10px; background:#fbfbfb;">
              <tr>
                <td style="padding:10px 14px;">'.h($labelCamper).'</td>
                <td align="right" style="padding:10px 14px;"><strong>'.h($r['camper'] ?? '').'</strong></td>
              </tr>
              <tr>
                <td style="padding:10px 14px;border-top:1px dashed #DDD;">'.h($labelDates).'</td>
                <td align="right" style="padding:10px 14px;border-top:1px dashed #DDD;">'
                    .h($startHumanMail).' &nbsp;&rarr;&nbsp; '.h($endHumanMail).'</td>
              </tr>
              <tr>
                <td style="padding:10px 14px;border-top:1px dashed #DDD;">'.h($labelNights).'</td>
                <td align="right" style="padding:10px 14px;border-top:1px dashed #DDD;">'.$nightsMail.'</td>
              </tr>
              <tr>
                <td style="padding:10px 14px;border-top:1px dashed #DDD;">'.h($labelStatus).'</td>
                <td align="right" style="padding:10px 14px;border-top:1px dashed #DDD;">'.h($statusTxt).'</td>
              </tr>
            </table>
            <p style="margin:14px 0 0 0;color:#555">'.h($mistake).'</p>
          </td>
        </tr>
        <tr><td style="padding:12px 22px;background:#f7f7f7;color:#7D7D7D;font-size:12px;">Alisios Van · Canary Islands</td></tr>
      </table>
    </td></tr>
  </table>
</div>';

                $alt = h($header)."\n"
                    . sprintf(__('email.cancel.subject'), (int)$r['id'])."\n"
                    . ($labelCamper.": ".($r['camper'] ?? '')."\n")
                    . ($labelDates.": {$startHumanMail} -> {$endHumanMail}\n")
                    . ($labelNights.": {$nightsMail}\n")
                    . ($labelStatus.": {$statusTxt}\n");

                $mail = buildMailerFromEnv();
                if ($mail) {
                    try {
                        $mail->addAddress($toEmail, $toName ?: $toEmail);
                        $bcc = env('SMTP_TO', env('MAIL_BCC',''));
                        if (!empty($bcc)) $mail->addBCC($bcc);
                        $mail->Subject = $subject;
                        $mail->isHTML(true);
                        $mail->Body    = $html;
                        $mail->AltBody = $alt;
                        $mail->send();

                        if (columnExists($pdo, 'reservations', 'cancellation_email_sent_at')) {
                            $pdo->prepare("UPDATE reservations SET cancellation_email_sent_at = NOW() WHERE id=? LIMIT 1")
                                ->execute([$rid]);
                        }
                    } catch (MailException $e) {
                        error_log('Cancel mail error: '.$e->getMessage());
                    }
                } else {
                    error_log('Cancel mail skipped: missing SMTP config.');
                }
            } else {
                error_log('Cancel mail skipped: no customer email found.');
            }
        }
    } catch (Throwable $e) {
        error_log('Cancel mail exception: '.$e->getMessage());
    }

    // 2) Redirección post-cancel (después de mandar email, etc.)
    $qs = 'rid='.$rid.'&lang='.$LC;
    if (!$adminACT && $t) { $qs .= '&t='.urlencode($t); }
    if ($refundNote) { $qs .= '&rn='.urlencode($refundNote); } // nota técnica, opcional
    $target = $adminACT ? 'manage-admin.php' : 'manage.php';
    header('Location: '.$target.'?'.$qs.'&m=cancelled');
    exit;
}

// Mensajes de la vista
if (isset($_GET['m']) && $_GET['m']==='cancelled') {
    $msgTop = t('alert.cancelled');
} elseif (isset($_GET['m']) && $_GET['m']==='already') {
    $msgTop = t('alert.already');
}
if (isset($_GET['rn'])) {
    $refundNote = (string)$_GET['rn']; // nota técnica (solo admin normalmente)
}

// --- Presentación -----------------------------------------------------------
$startHuman = date('j M Y', strtotime($r['start_date']));
$endHuman   = date('j M Y', strtotime($r['end_date']));
?>
<!doctype html>
<html lang="<?= h($LC) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h(sprintf(t('page.title'), (int)$r['id'])) ?></title>

    <!-- evita traducción automática de Chrome -->
    <meta name="google" content="notranslate">

    <!-- Fuentes y CSS globales que ya usas en el sitio -->
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">

    <!-- Bootstrap + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Otros assets que ya tenías -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flag-icons/css/flag-icons.min.css">
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/manage.css">
    <link rel="stylesheet" href="css/cookies.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="js/header.js" defer></script>
    <script src="js/cookies.js" defer></script>

    <style>
        :root { --header-bg-rgb: 84,70,62; } /* #54463E */
        .summary{ background: rgba(255,255,255,.6); border: 1px solid rgba(0,0,0,.06);
            border-radius: 12px; padding: 12px 14px; }
        .rowx{ display:flex; justify-content:space-between; padding: 8px 0; }
        .rowx + .rowx{ border-top: 1px dashed rgba(0,0,0,.08); }
        .badge-soft{ background: rgba(128,193,208,.15); color: var(--text-principal);
            border-radius: 999px; padding: .35rem .75rem; font-weight: 600; }
        .cardy{ background: var(--color-blanco); border-radius: var(--border-radius);
            box-shadow: var(--box-shadow-medium); border: 1px solid rgba(0,0,0,.04);
            padding: clamp(1rem, 2vw, 1.5rem); }
        .total{ font-weight:700; }
    </style>
</head>

<body>
<?php include 'inc/header.inc'; ?>

<section class="page-hero manage-hero">
    <div class="page-hero__content">
        <h1 class="page-hero__title"><?= h(sprintf(t('hero.title'), (int)$r['id'])) ?></h1>
        <p class="mt-2"><?= $adminUI ? h(t('hero.subtitle.admin')) : h(t('hero.subtitle.customer')) ?></p>
    </div>
</section>

<main class="wrap">
    <?php if (!empty($msgTop)): ?>
        <div class="alert alert-success"><?= h($msgTop) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['m']) && $_GET['m']==='cancelled'): ?>
        <div class="alert alert-info small mb-3">
            <?= h($adminUI ? t('note.admin') : t('note.customer')) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($refundNote)): ?>
        <div class="alert alert-secondary small mb-3"><?= h($refundNote) ?></div>
    <?php endif; ?>

    <div class="cardy mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div class="flex-grow-1">
                <h2 class="h5 mb-2"><?= h(t('section.trip')) ?></h2>
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <span class="badge-soft"><i class="bi bi-truck"></i> <?= h($r['camper']) ?></span>
                    <span class="badge-soft"><i class="bi bi-moon-stars"></i> <?= (int)$nights ?> <?= h($nights===1 ? t('badge.night.one') : t('badge.night.other')) ?></span>
                </div>
                <p class="mb-1"><strong><?= h(t('label.dates')) ?>:</strong>
                    <span style="white-space:nowrap"><?= h($startHuman) ?>&nbsp;→&nbsp;<?= h($endHuman) ?></span>
                </p>
                <p class="mb-2">
                    <strong><?= h(t('label.status')) ?>:</strong>
                    <?= h(status_label($r['status'])) ?>
                </p>

                <?php if (strpos((string)$r['status'],'cancelled') !== 0): ?>
                    <form method="post" onsubmit="return confirm('<?= h(t('confirm.cancel')) ?>');">
                        <input type="hidden" name="action" value="cancel">
                        <?php if ($adminUI): ?>
                            <p class="text-muted"><?= h(t('note.admin')) ?></p>
                        <?php else: ?>
                            <p class="text-warning"><?= h(t('note.customer')) ?></p>
                        <?php endif; ?>
                        <button class="btn <?= $adminUI ? 'btn-danger' : 'btn-outline-secondary' ?>">
                            <i class="bi bi-x-circle"></i> <?= h(t('btn.cancel')) ?>
                        </button>
                    </form>
                <?php else: ?>
                    <p class="text-muted"><?= h(t('label.cancelledAt')) ?> <?= h($r['cancelled_at'] ?? '') ?></p>
                <?php endif; ?>
            </div>

            <div style="min-width:260px;max-width:320px;" class="ms-auto">
                <h3 class="h6 mb-2"><?= h(t('section.payment')) ?></h3>
                <div class="summary">
                    <div class="rowx">
                        <span><?= h(t('label.priceNight')) ?></span>
                        <span>€<?= number_format($price, 2) ?></span>
                    </div>
                    <div class="rowx">
                        <span><?= h(t('label.nights')) ?></span>
                        <span><?= (int)$nights ?></span>
                    </div>
                    <div class="rowx total">
                        <span><?= __('Total') ?></span>
                        <span>€<?= number_format($total, 2) ?></span>
                    </div>
                    <div class="rowx" style="border-top:1px dashed rgba(0,0,0,.08); margin-top:6px;"></div>
                    <div class="rowx total">
                        <span><?= h(t('label.depositPaid')) ?></span>
                        <span>€<?= number_format($deposit, 2) ?></span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <p class="text-muted small">
        <?= h(t('footer.questions')) ?> <a href="mailto:alisios.van@gmail.com">alisios.van@gmail.com</a>.
    </p>
</main>

<?php include 'inc/footer.inc'; ?>

</body>
</html>
