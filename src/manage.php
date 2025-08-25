<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

// Composer + .env desde /secure
require_once __DIR__ . '/../config/bootstrap_env.php';
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
        'label.nights'      => 'Nächte',
        'label.depositPaid' => 'Anzahlung bezahlt',

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
        'label.nights'      => 'Notti',
        'label.depositPaid' => 'Deposito pagato',

        'footer.questions'  => 'Domande? Scrivici a',
    ],
];

function t(string $key, ...$args): string {
    global $STRINGS, $LC;
    $base = $STRINGS[$LC][$key] ?? ($STRINGS['en'][$key] ?? $key);
    return $args ? vsprintf($base, $args) : $base;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* -----------------------------------------------------------
 * Helpers / DB / Stripe / Mail (igual que tenías)
 * ---------------------------------------------------------*/
function admin_key(): ?string {
    $envKey = env('ADMIN_KEY','');
    if (!$envKey) return null;

    $k = $_GET['key'] ?? ($_COOKIE['admin_key'] ?? '');
    if ($k && hash_equals($envKey, (string)$k)) {
        if (empty($_COOKIE['admin_key'])) {
            setcookie('admin_key', (string)$k, [
                'expires'  => time() + 60*60*24*30,
                'path'     => '/',
                'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        return (string)$k;
    }
    return null;
}

function columnExists(PDO $pdo, string $table, string $column): bool {
    $sql = "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([':t'=>$table, ':c'=>$column]);
    return (bool)$st->fetchColumn();
}

/**
 * Intenta obtener email y nombre del cliente
 */
function getReservationContact(PDO $pdo, array $r): array {
    try {
        if (columnExists($pdo, 'reservations', 'customer_email') && !empty($r['customer_email'])) {
            return ['email' => (string)$r['customer_email'], 'name' => (string)($r['customer_name'] ?? '')];
        }
        if (columnExists($pdo, 'reservations', 'email') && !empty($r['email'])) {
            return ['email' => (string)$r['email'], 'name' => (string)($r['name'] ?? '')];
        }
    } catch (Throwable $e) { /* ignore */ }

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
    } catch (Throwable $e) { /* ignore */ }

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
    } catch (Throwable $e) { /* ignore */ }

    return ['email'=>'', 'name'=>''];
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
    return $mail;
}

// --- Input -----------------------------------------------------------------
$rid = (int)($_GET['rid'] ?? 0);
$t   = $_GET['t'] ?? '';
$ADMIN_KEY = admin_key();
$admin = $ADMIN_KEY !== null;

if(!$rid){
    http_response_code(400); echo t('error.missingRid'); exit;
}

// --- Carga reserva ----------------------------------------------------------
if ($admin) {
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
    echo $admin ? t('error.notfound') : t('error.invalidLink');
    exit;
}

// --- Datos útiles -----------------------------------------------------------
$nights = (int)((new DateTime($r['start_date']))->diff(new DateTime($r['end_date']))->format('%a'));
$price  = (float)$r['price_per_night'];
$deposit = $price; // depósito = 1 noche según tu política

// --- Cancelación ------------------------------------------------------------
$msgTop = '';
$refundNote = '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel'){
    // si ya estaba cancelada
    if (strpos((string)$r['status'],'cancelled') === 0) {
        $qs = 'rid='.$rid.($admin ? '&key='.urlencode($ADMIN_KEY) : ($t ? '&t='.urlencode($t) : '')).'&lang='.$LC;
        header('Location: manage.php?'.$qs.'&m=already'); exit;
    }

    $newStatus = $admin ? 'cancelled_by_admin' : 'cancelled_by_customer';

    if ($admin && !empty($r['stripe_payment_intent'])) {
        // Admin: intenta reembolso del depósito
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
                Refund::create(['payment_intent' => $r['stripe_payment_intent']]); // reembolso total del depósito
                $refundNote = 'Refund issued for the deposit.';
            } else {
                $refundNote = 'Charge has already been refunded.';
            }
        } catch (Throwable $e) {
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
        // refleja el nuevo estado en $r para el email
        $r['status'] = $newStatus;
        $r['cancelled_at'] = date('Y-m-d H:i:s');
    } catch (Throwable $e) {
        $up = $pdo->prepare("UPDATE reservations SET status='cancelled', cancelled_at=NOW() WHERE id=? LIMIT 1");
        $up->execute([$rid]);
        $r['status'] = 'cancelled';
        $r['cancelled_at'] = date('Y-m-d H:i:s');
    }

    // ======= Email de confirmación de cancelación (igual que tenías) =======
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
                $startHuman = date('j M Y', strtotime($r['start_date']));
                $endHuman   = date('j M Y', strtotime($r['end_date']));
                $nightsMail = (int)((new DateTime($r['start_date']))->diff(new DateTime($r['end_date']))->format('%a'));

                $subject = "Reservation #{$r['id']} cancelled";
                $lead    = $admin
                    ? "Your reservation has been cancelled by our team. The deposit has been (or will be) refunded."
                    : "Your reservation has been cancelled as requested. Per our policy, the deposit is non-refundable.";

                $html = '
<div style="font-family:Arial,sans-serif;line-height:1.55;color:#333;background:#e8e6e4;padding:24px;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
    <tr><td align="center">
      <table role="presentation" width="640" cellspacing="0" cellpadding="0"
             style="background:#fff;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.10);overflow:hidden;border:1px solid rgba(0,0,0,.04);">
        <tr>
          <td style="background:#80C1D0;color:#fff;padding:18px 22px;">
            <div style="font-size:22px;font-weight:700;">Reservation cancelled</div>
            <div style="opacity:.9;margin-top:4px;">#'.(int)$r['id'].' — Alisios Van</div>
          </td>
        </tr>
        <tr>
          <td style="padding:20px 22px;">
            <p style="margin:0 0 10px 0">'.htmlspecialchars($lead, ENT_QUOTES, 'UTF-8').'</p>
            <p style="margin:0 0 14px 0">Summary:</p>
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                   style="border:1px solid rgba(0,0,0,0.06); border-radius:10px; background:#fbfbfb;">
              <tr>
                <td style="padding:10px 14px;">Camper</td>
                <td align="right" style="padding:10px 14px;"><strong>'.htmlspecialchars($r['camper'] ?? '', ENT_QUOTES, 'UTF-8').'</strong></td>
              </tr>
              <tr>
                <td style="padding:10px 14px;border-top:1px dashed #DDD;">Dates</td>
                <td align="right" style="padding:10px 14px;border-top:1px dashed #DDD;">'
                    .htmlspecialchars($startHuman, ENT_QUOTES, 'UTF-8').' &nbsp;&rarr;&nbsp; '
                    .htmlspecialchars($endHuman, ENT_QUOTES, 'UTF-8').'</td>
              </tr>
              <tr>
                <td style="padding:10px 14px;border-top:1px dashed #DDD;">Nights</td>
                <td align="right" style="padding:10px 14px;border-top:1px dashed #DDD;">'.$nightsMail.'</td>
              </tr>
              <tr>
                <td style="padding:10px 14px;border-top:1px dashed #DDD;">Status</td>
                <td align="right" style="padding:10px 14px;border-top:1px dashed #DDD;">'.htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8').'</td>
              </tr>
            </table>
            <p style="margin:14px 0 0 0;color:#555">If this was a mistake, reply to this email and we\'ll help.</p>
          </td>
        </tr>
        <tr><td style="padding:12px 22px;background:#f7f7f7;color:#7D7D7D;font-size:12px;">Alisios Van · Canary Islands</td></tr>
      </table>
    </td></tr>
  </table>
</div>';

                $alt = "Reservation cancelled\n"
                    . "Reservation #{$r['id']}\n"
                    . "Camper: ".($r['camper'] ?? '')."\n"
                    . "Dates: {$startHuman} -> {$endHuman}\n"
                    . "Status: {$r['status']}\n"
                    . ($admin ? "Refund: deposit refunded (if applicable)\n" : "Note: deposit non-refundable per policy\n");

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

    // Redirección post-cancel
    $qs = 'rid='.$rid.($admin ? '&key='.urlencode($ADMIN_KEY) : ($t ? '&t='.urlencode($t) : '')).'&lang='.$LC;
    if ($refundNote) $qs .= '&rn='.urlencode($refundNote);
    header('Location: manage.php?'.$qs.'&m=cancelled');
    exit;
}

// Mensajes de la vista
if (isset($_GET['m']) && $_GET['m']==='cancelled') {
    $msgTop = t('alert.cancelled');
} elseif (isset($_GET['m']) && $_GET['m']==='already') {
    $msgTop = t('alert.already');
}
if (isset($_GET['rn'])) {
    $refundNote = (string)$_GET['rn']; // nota en inglés tal como viene del proceso de cancelación
}

// --- Presentación -----------------------------------------------------------
$startHuman = date('j M Y', strtotime($r['start_date']));
$endHuman   = date('j M Y', strtotime($r['end_date']));
?>
<!doctype html>
<html lang="<?= h($LC) ?>"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h(sprintf(t('page.title'), (int)$r['id'])) ?></title>

    <!-- evita traducción automática de Chrome -->
    <meta name="google" content="notranslate">

    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" defer></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flag-icons/css/flag-icons.min.css">

    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/manage.css">
    <link rel="stylesheet" href="css/cookies.css">

    <script src="js/header.js" defer></script>
    <script src="js/cookies.js" defer></script>

    <style>
        :root { --header-bg-rgb: 84,70,62; } /* #54463E */
    </style>
</head>

<body>
<?php include 'inc/header.inc'; ?>

<section class="page-hero manage-hero">
    <div class="page-hero__content">
        <h1 class="page-hero__title"><?= h(sprintf(t('hero.title'), (int)$r['id'])) ?></h1>
        <p class="mt-2"><?= $admin ? h(t('hero.subtitle.admin')) : h(t('hero.subtitle.customer')) ?></p>
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
                <h2 class="h5 mb-2"><?= h(t('section.trip')) ?></h2>
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <span class="badge-soft"><i class="bi bi-truck"></i> <?= h($r['camper']) ?></span>
                    <span class="badge-soft"><i class="bi bi-moon-stars"></i> <?= (int)$nights ?> <?= h($nights===1 ? t('badge.night.one') : t('badge.night.other')) ?></span>
                </div>
                <p class="mb-1"><strong><?= h(t('label.dates')) ?>:</strong>
                    <span style="white-space:nowrap"><?= h($startHuman) ?>&nbsp;→&nbsp;<?= h($endHuman) ?></span>
                </p>
                <p class="mb-2"><strong><?= h(t('label.status')) ?>:</strong> <?= h($r['status']) ?></p>

                <?php if (strpos((string)$r['status'],'cancelled') !== 0): ?>
                    <form method="post" onsubmit="return confirm('<?= h(t('confirm.cancel')) ?>');">
                        <input type="hidden" name="action" value="cancel">
                        <?php if ($admin): ?>
                            <p class="text-muted"><?= h(t('note.admin')) ?></p>
                        <?php else: ?>
                            <p class="text-warning"><?= h(t('note.customer')) ?></p>
                        <?php endif; ?>
                        <button class="btn <?= $admin ? 'btn-danger' : 'btn-outline-secondary' ?>">
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
                    <div class="rowx"><span><?= h(t('label.priceNight')) ?></span><span>€<?= number_format($price, 2) ?></span></div>
                    <div class="rowx"><span><?= h(t('label.nights')) ?></span><span><?= (int)$nights ?></span></div>
                    <div class="rowx total"><span><?= h(t('label.depositPaid')) ?></span><span>€<?= number_format($deposit, 2) ?></span></div>
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
