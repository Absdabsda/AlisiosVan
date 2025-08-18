<?php
declare(strict_types=1);

require_once '/home/u647357107/domains/alisiosvan.com/secure/bootstrap.php';
require __DIR__ . '/../config/db.php';

use Stripe\Stripe;
use Stripe\Checkout\Session as CheckoutSession;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

$pdo = get_pdo();

// (recomendado en producción)
ini_set('display_errors','0');
ini_set('log_errors','1');
ini_set('error_log','/home/u647357107/domains/alisiosvan.com/secure/php-errors.log');

$sk = env('STRIPE_SECRET','');
if (!$sk) { http_response_code(500); exit('Stripe not configured'); }
Stripe::setApiKey($sk);

$sessionId = $_GET['session_id'] ?? '';
if (!$sessionId) { http_response_code(400); echo 'Missing session_id'; exit; }

// Helpers --------------------------------------------------------------------
function columnExists(PDO $pdo, string $table, string $column): bool {
    $sql = "SELECT 1
              FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :t
               AND COLUMN_NAME = :c
             LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([':t'=>$table, ':c'=>$column]);
    return (bool)$st->fetchColumn();
}

function publicBaseUrl(): string {
    $env = rtrim(env('PUBLIC_BASE_URL',''), '/');
    if ($env) return $env;
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return rtrim("$scheme://$host$dir", '/');
}

function norm($s){ return mb_strtolower(trim((string)$s)); }

try {
    // Recupera la sesión e incluimos invoice/customer
    $session = CheckoutSession::retrieve($sessionId, [
        'expand' => ['payment_intent', 'invoice', 'customer']
    ]);

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
        $up->execute([':pi' => (string)$session->payment_intent, ':ssid' => $sessionId]);

        // === Vincular la reserva con un cliente (fallback al webhook) =======
        $stripeCustomerId = null;
        $piLink = $session->payment_intent ?? null;

        // Candidatos de email desde Stripe
        $emailCands = [];
        $emailCands[] = $session->customer_details->email ?? null;
        if (!empty($session->customer)) {
            if (is_object($session->customer)) {
                $emailCands[] = $session->customer->email ?? null;
                $stripeCustomerId = $session->customer->id ?? null;
            } else {
                $stripeCustomerId = (string)$session->customer;
            }
        }
        if ($piLink && isset($piLink->charges->data[0])) {
            $emailCands[] = $piLink->charges->data[0]->billing_details->email ?? null;
            $emailCands[] = $piLink->receipt_email ?? null;
        }
        // Customer ID también puede venir del PaymentIntent
        if (!$stripeCustomerId && $piLink && !empty($piLink->customer)) {
            $stripeCustomerId = is_object($piLink->customer) ? ($piLink->customer->id ?? null) : (string)$piLink->customer;
        }

        // Normaliza y elige el primer email válido
        $customerEmailNorm = '';
        foreach ($emailCands as $e) {
            if ($e && filter_var($e, FILTER_VALIDATE_EMAIL)) {
                $customerEmailNorm = norm($e);
                break;
            }
        }

        if ($customerEmailNorm) {
            // UPSERT en customers
            $sel = $pdo->prepare("SELECT id, stripe_customer_id FROM customers WHERE email_norm=? LIMIT 1");
            $sel->execute([$customerEmailNorm]);

            if ($c = $sel->fetch(PDO::FETCH_ASSOC)) {
                $customerId = (int)$c['id'];
                if (!empty($stripeCustomerId) && empty($c['stripe_customer_id'])) {
                    $pdo->prepare("UPDATE customers SET stripe_customer_id=? WHERE id=? LIMIT 1")
                        ->execute([$stripeCustomerId, $customerId]);
                }
            } else {
                // Partimos nombre "First Last"
                $full  = trim((string)($session->customer_details->name ?? ''));
                $parts = preg_split('/\s+/u', $full);
                $first = $parts[0] ?? '';
                $last  = $parts ? implode(' ', array_slice($parts, 1)) : '';

                $ins = $pdo->prepare("INSERT INTO customers (first_name,last_name,email,email_norm,stripe_customer_id,created_at)
                                      VALUES (?,?,?,?,?,NOW())");
                $ins->execute([$first, $last, $customerEmailNorm, $customerEmailNorm, $stripeCustomerId ?? null]);
                $customerId = (int)$pdo->lastInsertId();
            }

            // Enlaza la reserva (si no estaba ya enlazada)
            $pdo->prepare("UPDATE reservations
                              SET customer_id = COALESCE(?, customer_id)
                            WHERE stripe_session_id = ? LIMIT 1")
                ->execute([$customerId, $sessionId]);
        }

        // --- Invoice (puede tardar) ----------------------------------------
        $invoiceId = null; $invoicePdf = null; $invoiceUrl = null;
        $invoiceStatus = null; $invoicePaid = false;

        // 1) session->invoice
        if (!empty($session->invoice)) {
            $invoiceId = is_object($session->invoice) ? $session->invoice->id : (string)$session->invoice;
        }
        // 2) payment_intent->invoice
        $pi = $session->payment_intent ?? null;
        if (!$invoiceId && $pi && !empty($pi->invoice)) {
            $invoiceId = is_object($pi->invoice) ? $pi->invoice->id : (string)$pi->invoice;
        }
        // 3) buscar por payment_intent
        if (!$invoiceId && $pi && !empty($pi->id)) {
            try {
                $list = \Stripe\Invoice::all(['limit' => 1, 'payment_intent' => $pi->id]);
                if (!empty($list->data[0])) { $invoiceId = $list->data[0]->id; }
            } catch (Throwable $e) { /* ignore */ }
        }
        // 4) reintento hasta que tenga URL/PDF/estado (~15s)
        if ($invoiceId) {
            for ($i=0; $i<30; $i++) { // 30 * 0.5s = 15s
                $inv = \Stripe\Invoice::retrieve($invoiceId);
                $invoicePdf    = $inv->invoice_pdf ?? null;
                $invoiceUrl    = $inv->hosted_invoice_url ?? null;
                $invoiceStatus = $inv->status ?? null;
                $invoicePaid   = ($invoiceStatus === 'paid');
                if ($invoicePdf || $invoiceUrl || $invoicePaid) { break; }
                usleep(500000); // 0.5s
            }
        }

        // Guarda en BD si existen las columnas
        if ($invoiceId && columnExists($pdo, 'reservations', 'stripe_invoice_id')) {
            $pdo->prepare("UPDATE reservations SET stripe_invoice_id=:iid WHERE stripe_session_id=:ssid LIMIT 1")
                ->execute([':iid'=>$invoiceId, ':ssid'=>$sessionId]);
        }
        if (($invoicePdf || $invoiceUrl)
            && columnExists($pdo,'reservations','stripe_invoice_pdf')
            && columnExists($pdo,'reservations','stripe_invoice_url')) {
            $pdo->prepare("UPDATE reservations
                              SET stripe_invoice_pdf=:pdf, stripe_invoice_url=:url
                            WHERE stripe_session_id=:ssid LIMIT 1")
                ->execute([':pdf'=>$invoicePdf, ':url'=>$invoiceUrl, ':ssid'=>$sessionId]);
        }

        // --- Datos de la reserva --------------------------------------------
        $hasManage = columnExists($pdo, 'reservations', 'manage_token');
        $select = "
            SELECT r.id, r.start_date, r.end_date,
                   c.name AS camper, c.price_per_night,
                   DATEDIFF(r.end_date, r.start_date) AS nights";
        if ($hasManage) { $select .= ", r.manage_token"; }
        $select .= "
              FROM reservations r
              JOIN campers c ON c.id = r.camper_id
             WHERE r.stripe_session_id = :ssid
             LIMIT 1";
        $st = $pdo->prepare($select);
        $st->execute([':ssid' => $sessionId]);
        $res = $st->fetch(PDO::FETCH_ASSOC);
        if (!$res) { throw new Exception('Reservation not found after payment.'); }

        $nights = (int)$res['nights'];
        $price  = (float)$res['price_per_night'];
        $total  = $nights * $price;

        // Manage URL (si existe manage_token)
        $manageUrl = null;
        if ($hasManage && !empty($res['manage_token'])) {
            $manageUrl = publicBaseUrl() . '/manage.php?rid='.(int)$res['id'].'&t='.$res['manage_token'];
        }

        // --- Email -----------------------------------------------------------
        $customerEmail = $session->customer_details->email ?? $session->customer_email ?? '';
        $customerName  = $session->customer_details->name  ?? '';

        // Recibo Stripe (opcional)
        $receiptUrl = null;
        try {
            $pi = $session->payment_intent ?? null;
            if ($pi && isset($pi->charges->data[0])) {
                $receiptUrl = $pi->charges->data[0]->receipt_url ?? null;
            }
        } catch (Throwable $e) { /* ignore */ }

        // Fechas
        $startFmt   = date('Y-m-d', strtotime($res['start_date']));
        $endFmt     = date('Y-m-d', strtotime($res['end_date']));
        $startHuman = date('j M Y', strtotime($res['start_date']));
        $endHuman   = date('j M Y', strtotime($res['end_date']));

        // .ics (evento de día completo)
        $endExclusive = (new DateTime($endFmt))->modify('+1 day')->format('Ymd');
        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR','VERSION:2.0','PRODID:-//Alisios Van//EN','BEGIN:VEVENT',
            'UID:' . (int)$res['id'] . '@alisiosvan',
            'DTSTAMP:' . gmdate('Ymd\THis\Z'),
            'DTSTART;VALUE=DATE:' . date('Ymd', strtotime($startFmt)),
            'DTEND;VALUE=DATE:' . $endExclusive,
            'SUMMARY:' . 'Alisios Van · ' . $res['camper'],
            'DESCRIPTION:Reservation confirmed',
            'END:VEVENT','END:VCALENDAR'
        ]);

        // --- TABLA RESUMEN (email-safe) ------------------------------------
        $summaryTable = '
<table role="presentation" width="100%" cellspacing="0" cellpadding="0"
       style="border:1px solid rgba(0,0,0,0.06); border-radius:10px; background:#fbfbfb; margin-bottom:14px;">
  <tr>
    <td style="padding:0">
      <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
             style="font-family:Arial, sans-serif; font-size:14px; line-height:20px; mso-line-height-rule:exactly;">
        <tr>
          <td style="padding:10px 14px;">Camper</td>
          <td align="right" style="padding:10px 14px;"><strong>'.htmlspecialchars($res['camper'],ENT_QUOTES,'UTF-8').'</strong></td>
        </tr>
        <tr>
          <td style="padding:10px 14px; border-top:1px dashed #DDD;">Dates</td>
          <td align="right" style="padding:10px 14px; border-top:1px dashed #DDD; white-space:nowrap;">'
            .htmlspecialchars($startHuman,ENT_QUOTES,'UTF-8').' &nbsp;&rarr;&nbsp; '
            .htmlspecialchars($endHuman,ENT_QUOTES,'UTF-8').'</td>
        </tr>
        <tr>
          <td style="padding:10px 14px; border-top:1px dashed #DDD;">Nights</td>
          <td align="right" style="padding:10px 14px; border-top:1px dashed #DDD;">'.$nights.'</td>
        </tr>
        <tr>
          <td style="padding:10px 14px; border-top:1px dashed #DDD;">Price/night</td>
          <td align="right" style="padding:10px 14px; border-top:1px dashed #DDD;">€'.number_format($price,2).'</td>
        </tr>
        <tr>
          <td style="padding:10px 14px; border-top:1px dashed #DDD; font-weight:700;">Total price</td>
          <td align="right" style="padding:10px 14px; border-top:1px dashed #DDD; font-weight:700;">€'.number_format($total,2).'</td>
        </tr>
      </table>
    </td>
  </tr>
</table>';

        // Plantilla email -----------------------------------------------------
        $html = '
<div style="font-family:Arial,sans-serif;line-height:1.55;color:#333;background:#e8e6e4;padding:24px;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
    <tr>
      <td align="center">
        <table role="presentation" width="640" cellspacing="0" cellpadding="0"
               style="background:#FFFFFF;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,0.10);overflow:hidden;border:1px solid rgba(0,0,0,0.04);">
          <tr>
            <td style="background:#80C1D0;color:#fff;padding:18px 22px;">
              <div style="font-size:22px;font-weight:700;margin:0;">Payment confirmed</div>
              <div style="opacity:.9;margin-top:4px;">Reservation #'.(int)$res['id'].' — Thank you for choosing Alisios Van</div>
            </td>
          </tr>
          <tr>
            <td style="padding:20px 22px;">
              <p style="margin:0 0 8px 0">Hi'.($customerName ? ' '.htmlspecialchars($customerName,ENT_QUOTES,'UTF-8') : '').',</p>
              <p style="margin:0 0 12px 0">Your reservation is <strong>confirmed</strong>. Here are your details:</p>

              '.$summaryTable.'

              '.
            ($receiptUrl ? '<p style="margin:0 0 12px 0">Receipt: <a href="'.htmlspecialchars($receiptUrl,ENT_QUOTES,'UTF-8').'" style="color:#5698A6;">View Stripe receipt</a></p>' : '')
            .(($invoicePdf || $invoiceUrl) ?
                '<p style="margin:0 0 12px 0">' .
                ($invoicePdf ? 'Invoice: <a href="'.htmlspecialchars($invoicePdf,ENT_QUOTES,'UTF-8').'" style="color:#5698A6;">Download PDF</a><br>' : '') .
                ($invoiceUrl ? '<a href="'.htmlspecialchars($invoiceUrl,ENT_QUOTES,'UTF-8').'" style="color:#5698A6;">View invoice online</a>' : '') .
                '</p>' : ''
            ).'
              <p style="margin:0 0 12px 0">We\'ve attached a calendar file (.ics) so you can add the trip to your calendar.</p>
              <p style="margin:0;color:#555">Questions? Write us at <a href="mailto:alisios.van@gmail.com" style="color:#5698A6;">alisios.van@gmail.com</a>.</p>
            </td>
          </tr>
          <tr>
            <td style="padding:12px 22px;background:#f7f7f7;color:#7D7D7D;font-size:12px;">Alisios Van · Canary Islands</td>
          </tr>
        </table>

        '.($manageUrl
                ? '<div style="max-width:640px;margin:0 auto;padding:12px 0 0 0;font-family:Arial,sans-serif;">
                 <p style="margin:12px 0">Manage your booking:
                   <a href="'.htmlspecialchars($manageUrl,ENT_QUOTES,'UTF-8').'" style="color:#5698A6;">Open management page</a>
                 </p>
               </div>'
                : ''
            ).'
      </td>
    </tr>
  </table>
</div>';

        $alt = "Payment confirmed\n".
            "Reservation #{$res['id']}\n".
            "Camper: {$res['camper']}\n".
            "Dates: {$startHuman} -> {$endHuman}\n".
            "Nights: {$nights}\n".
            "Price/night: €".number_format($price,2)."\n".
            "Total price: €".number_format($total,2)."\n".
            ($receiptUrl ? "Receipt: $receiptUrl\n" : "") .
            (($invoicePdf || $invoiceUrl) ? "Invoice: ".($invoicePdf ?: $invoiceUrl)."\n" : "") .
            ($manageUrl ? "Manage: $manageUrl\n" : "") .
            "We attach a .ics file to add the trip to your calendar.\n";

        // SMTP config ---------------------------------------------------------
        $host      = env('SMTP_HOST', env('MAIL_HOST',''));
        $port      = (int) env('SMTP_PORT', env('MAIL_PORT', 587));
        $user      = env('SMTP_USER', env('MAIL_USER',''));
        $pass      = env('SMTP_PASS', env('MAIL_PASS',''));
        $secureRaw = strtolower((string) env('SMTP_SECURE','tls')); // ssl|tls
        $fromEmail = env('SMTP_FROM', env('MAIL_FROM', $user));
        $fromName  = env('SMTP_FROM_NAME', env('MAIL_FROM_NAME','Alisios Van'));
        $adminTo   = env('SMTP_TO', env('MAIL_BCC',''));

        // Helper: descargar URL (cURL)
        $fetch = function(string $url): ?string {
            if (!$url) return null;
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $data = curl_exec($ch);
            $ok = ($data !== false) && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
            curl_close($ch);
            return $ok ? $data : null;
        };

        // --------- Lógica de envío: solo si la factura ya está lista --------
        $invoiceReady = (bool)($invoicePdf || $invoiceUrl || $invoicePaid);

        // ¿Ya se envió?
        $emailAlreadySent = false;
        if (columnExists($pdo, 'reservations', 'confirmation_email_sent_at')) {
            $q = $pdo->prepare("SELECT confirmation_email_sent_at FROM reservations WHERE stripe_session_id = :ssid LIMIT 1");
            $q->execute([':ssid' => $sessionId]);
            $emailAlreadySent = (bool)$q->fetchColumn();
        }

        $shouldSendEmail = (!$emailAlreadySent && $invoiceReady && $customerEmail && $host && $user && $pass);

        if ($shouldSendEmail) {
            $sentOk = false;
            try {
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
                $mail->addAddress($customerEmail, $customerName ?: $customerEmail);
                if (!empty($adminTo)) { $mail->addBCC($adminTo); }
                $mail->addReplyTo('alisios.van@gmail.com', 'Alisios Van');

                $mail->Subject = "Your reservation #{$res['id']} is confirmed";
                $mail->isHTML(true);
                $mail->Body    = $html;
                $mail->AltBody = $alt;

                // Adjunta ICS
                $mail->addStringAttachment($ics, 'alisiosvan-reservation.ics', 'base64', 'text/calendar');

                // Adjunta la factura PDF si está disponible
                if ($invoicePdf) {
                    $pdfData = $fetch($invoicePdf);
                    if ($pdfData) {
                        $mail->addStringAttachment($pdfData, "Invoice-{$res['id']}.pdf", 'base64', 'application/pdf');
                    } else {
                        error_log('Invoice PDF not downloaded for attachment.');
                    }
                }

                $mail->send();
                $sentOk = true;
            } catch (MailException $e) {
                error_log('Mail send error: '.$e->getMessage());
            }

            if ($sentOk && columnExists($pdo, 'reservations', 'confirmation_email_sent_at')) {
                $pdo->prepare("
                    UPDATE reservations
                       SET confirmation_email_sent_at = NOW()
                     WHERE stripe_session_id = :ssid
                       AND confirmation_email_sent_at IS NULL
                     LIMIT 1
                ")->execute([':ssid' => $sessionId]);
            }
        } else {
            if (!$invoiceReady) {
                error_log("Invoice not ready yet for session $sessionId; email postponed.");
            } elseif ($emailAlreadySent) {
                error_log("Email already sent for session $sessionId; skipping.");
            } else {
                error_log('Mail not sent: missing SMTP config or customer email.');
            }
        }

        // ------------------ Página (para navegador) --------------------------
        ?>
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Payment confirmed | Alisios Van</title>

            <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
            <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
            <link rel="stylesheet" href="css/estilos.css">
            <link rel="stylesheet" href="css/cookies.css">
            <script src="js/cookies.js" defer></script>

            <style>
                .page-hero{ background-image:url('img/landing-matcha.02.31.jpeg'); }
                .wrap{ max-width: 1000px; margin-inline:auto; padding: var(--spacing-l); }
                .cardy{ background: var(--color-blanco); border-radius: var(--border-radius);
                    box-shadow: var(--box-shadow-medium); border: 1px solid rgba(0,0,0,.04);
                    padding: clamp(1rem, 2vw, 1.5rem); }
                .badge-soft{ background: rgba(128,193,208,.15); color: var(--text-principal);
                    border-radius: 999px; padding: .35rem .75rem; font-weight: 600; }
                .summary{ background: rgba(255,255,255,.6); border: 1px solid rgba(0,0,0,.06);
                    border-radius: 12px; padding: 12px 14px; }
                .rowx{ display:flex; justify-content:space-between; padding: 8px 0; }
                .rowx + .rowx{ border-top: 1px dashed rgba(0,0,0,.08); }
                .total{ font-weight:700; }
            </style>
        </head>
        <body>
        <section class="page-hero">
            <div class="page-hero__content">
                <h1 class="page-hero__title">Payment confirmed!</h1>
                <p class="mt-2">Reservation #<?= (int)$res['id'] ?> · Thank you for choosing Alisios Van.</p>
            </div>
        </section>

        <main class="wrap">
            <div class="cardy mb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div class="flex-grow-1">
                        <h2 class="h4 mb-2">Your trip</h2>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="badge-soft"><i class="bi bi-truck"></i> <?= htmlspecialchars($res['camper'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="badge-soft"><i class="bi bi-moon-stars"></i> <?= (int)$nights ?> night<?= $nights>1?'s':'' ?></span>
                        </div>
                        <p class="mb-1"><strong>Dates:</strong> <?= htmlspecialchars($startHuman, ENT_QUOTES, 'UTF-8') ?> → <?= htmlspecialchars($endHuman, ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="mb-3 text-muted">We’ve emailed you the booking details and next steps.</p>

                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn" href="index.php"><i class="bi bi-house-door"></i> Back to Home</a>
                            <a class="btn btn-outline-secondary" href="campers.php"><i class="bi bi-truck"></i> Browse campers</a>
                            <button id="btnIcs" class="btn btn-outline-secondary"><i class="bi bi-calendar-event"></i> Add to calendar</button>
                            <?php if ($invoicePdf): ?>
                                <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($invoicePdf, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                                    <i class="bi bi-file-earmark-pdf"></i> Download invoice (PDF)
                                </a>
                            <?php endif; ?>
                            <?php if ($invoiceUrl): ?>
                                <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($invoiceUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                                    <i class="bi bi-receipt"></i> View invoice online
                                </a>
                            <?php endif; ?>
                            <?php if ($manageUrl): ?>
                                <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($manageUrl, ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="bi bi-gear"></i> Manage / cancel reservation
                                </a>
                            <?php endif; ?>
                            <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
                        </div>

                        <?php if (!$invoicePdf && !$invoiceUrl && !$invoiceId): ?>
                            <p class="text-muted mt-3 small">
                                Your invoice is being generated. It can take a little while. We’ll email it to you shortly. If you don't receive an email, refresh the page.
                            </p>
                        <?php elseif (!$invoicePdf && !$invoiceUrl): ?>
                            <p class="text-muted mt-3 small">
                                The invoice was created but the file isn’t ready yet. We’ll email it as soon as it’s available.
                            </p>
                        <?php endif; ?>
                    </div>

                    <div style="min-width:260px;max-width:320px;" class="ms-auto">
                        <h3 class="h6 mb-2">Payment</h3>
                        <div class="summary">
                            <div class="rowx"><span>Price/night</span><span>€<?= number_format((float)$price, 2) ?></span></div>
                            <div class="rowx"><span>Nights</span><span><?= (int)$nights ?></span></div>
                            <div class="rowx total"><span>Total price</span><span>€<?= number_format((float)$total, 2) ?></span></div>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-muted small">
                Questions? Email us at <a href="mailto:alisios.van@gmail.com">alisios.van@gmail.com</a>.
            </p>

            <!-- Data for ICS generation -->
            <div id="icsData"
                 data-title="<?= htmlspecialchars('Alisios Van · '.$res['camper'], ENT_QUOTES, 'UTF-8') ?>"
                 data-start="<?= htmlspecialchars($startFmt, ENT_QUOTES, 'UTF-8') ?>"
                 data-end="<?= htmlspecialchars($endFmt, ENT_QUOTES, 'UTF-8') ?>"
                 data-id="<?= (int)$res['id'] ?>"></div>
        </main>

        <script>
            (function () {
                const el = document.getElementById('icsData');
                if (!el) return;

                function ymd(s){ return (s||'').replaceAll('-',''); }

                function buildIcs() {
                    const title = el.dataset.title || 'Alisios Van';
                    const start = ymd(el.dataset.start);
                    const endD  = new Date(el.dataset.end); endD.setDate(endD.getDate() + 1); // DTEND exclusivo
                    const end   = ymd(endD.toISOString().slice(0,10));
                    const uid   = (el.dataset.id || '') + '@alisiosvan';
                    const dtstamp = new Date().toISOString().replace(/[-:]/g,'').split('.')[0] + 'Z';

                    return [
                        'BEGIN:VCALENDAR',
                        'VERSION:2.0',
                        'PRODID:-//Alisios Van//EN',
                        'METHOD:PUBLISH',
                        'BEGIN:VEVENT',
                        'UID:' + uid,
                        'DTSTAMP:' + dtstamp,
                        'DTSTART;VALUE=DATE:' + start,
                        'DTEND;VALUE=DATE:' + end,
                        'SUMMARY:' + title,
                        'DESCRIPTION:Reservation confirmed',
                        'END:VEVENT',
                        'END:VCALENDAR'
                    ].join('\r\n'); // <— IMPORTANTE: \r\n reales
                }

                function isIOS() {
                    return /iP(hone|od|ad)/i.test(navigator.userAgent);
                }

                document.getElementById('btnIcs')?.addEventListener('click', () => {
                    const ics = buildIcs();

                    // iOS (y algunos navegadores que no soportan download+blob): usar data: URL
                    if (isIOS()) {
                        const dataUrl = 'data:text/calendar;charset=utf-8,' + encodeURIComponent(ics);
                        // Abrimos en la misma pestaña para que iOS ofrezca “Abrir en Calendario”
                        window.location.href = dataUrl;
                        return;
                    }

                    // Resto: blob + descarga con filename .ics
                    try {
                        const blob = new Blob([ics], { type: 'text/calendar;charset=utf-8' });
                        const url  = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'alisiosvan-reservation.ics';
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        URL.revokeObjectURL(url);
                    } catch (e) {
                        // Fallback final a data: URL
                        const dataUrl = 'data:text/calendar;charset=utf-8,' + encodeURIComponent(ics);
                        window.open(dataUrl, '_blank', 'noopener');
                    }
                });
            })();
        </script>
        </body>
        </html>
        <?php
        exit;
    }

    // Not paid yet
    http_response_code(200);
    echo "Payment not completed yet. Status: " . htmlspecialchars($session->payment_status);

} catch (Throwable $e) {
    http_response_code(500);
    echo "Error: " . htmlspecialchars($e->getMessage());
}
