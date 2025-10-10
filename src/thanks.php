<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap_env.php';
require_once __DIR__ . '/../config/i18n-lite.php';
require_once __DIR__ . '/../config/db.php';

use Stripe\Stripe;
use Stripe\Checkout\Session as CheckoutSession;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

$pdo = get_pdo();

// Producción: log a fichero seguro
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
    // Recupera la sesión e incluimos charges para poder sacar receipt_url sin otra llamada
    $session = CheckoutSession::retrieve($sessionId, [
        'expand' => ['payment_intent.charges', 'invoice', 'customer']
    ]);

    // ====== Determinar y fijar idioma para esta ejecución ===================
    $lang = $GLOBALS['LANG'] ?? 'en';
    if (!empty($session->metadata->lang)) {
        $lang = preg_replace('/[^a-zA-Z_-]/', '', (string)$session->metadata->lang);
    } elseif (columnExists($pdo, 'reservations', 'lang')) {
        $q = $pdo->prepare("SELECT lang FROM reservations WHERE stripe_session_id = ? LIMIT 1");
        $q->execute([$sessionId]);
        $dbLang = $q->fetchColumn();
        if ($dbLang) { $lang = preg_replace('/[^a-zA-Z_-]/', '', (string)$dbLang); }
    }
    if (function_exists('i18n_set_locale')) {
        i18n_set_locale($lang);
    } else {
        $GLOBALS['LANG'] = $lang;
    }

    if ($session->payment_status === 'paid') {

        // ========= 1) Crear/asegurar la reserva en BD =========
        $selRes = $pdo->prepare("SELECT id FROM reservations WHERE stripe_session_id = ? LIMIT 1");
        $selRes->execute([$sessionId]);
        $reservationId = (int)($selRes->fetchColumn() ?: 0);

        // Datos desde metadata de la Session (checkout)
        $metaCamperId   = (int)($session->metadata->camper_id ?? 0);
        $metaStart      = (string)($session->metadata->start ?? '');
        $metaEnd        = (string)($session->metadata->end ?? '');
        $metaNights     = (int)($session->metadata->nights ?? 0);
        $metaUnit       = (float)($session->metadata->price_per_night_eur ?? 0);
        $metaTotalCents = (int)($session->metadata->total_due_cents ?? 0);
        $metaDepoCents  = (int)($session->metadata->deposit_cents ?? 0);
        $metaLang       = (string)($session->metadata->lang ?? ($GLOBALS['LANG'] ?? 'en'));

        // Normaliza fechas por si vienen vacías
        if (!$metaStart || !$metaEnd) {
            if (!empty($session->invoice) && is_object($session->invoice) && !empty($session->invoice->metadata)) {
                $metaStart = $metaStart ?: (string)($session->invoice->metadata->start ?? '');
                $metaEnd   = $metaEnd   ?: (string)($session->invoice->metadata->end ?? '');
            }
        }

        // Si no hay nights en metadata, calcúlalos
        if ($metaStart && $metaEnd && $metaNights <= 0) {
            try {
                $d1 = new DateTime($metaStart);
                $d2 = new DateTime($metaEnd);
                $metaNights = max(0, (int)$d1->diff($d2)->format('%a'));
            } catch (Throwable $e) { /* ignore */ }
        }

        // Si aún no existe la reserva, la creamos ahora
        if ($reservationId <= 0) {
            $hasManage = columnExists($pdo, 'reservations', 'manage_token');
            $hasLang   = columnExists($pdo, 'reservations', 'lang');
            $manageToken = $hasManage ? bin2hex(random_bytes(24)) : null;

            // Asegura precio unitario si faltara (no debería)
            if ($metaUnit <= 0 && $metaCamperId > 0) {
                $stp = $pdo->prepare("SELECT price_per_night FROM campers WHERE id=?");
                $stp->execute([$metaCamperId]);
                $metaUnit = (float)($stp->fetchColumn() ?: 0);
            }
            if ($metaTotalCents <= 0 && $metaUnit > 0 && $metaNights > 0) {
                $metaTotalCents = (int)round($metaUnit * 100 * $metaNights);
            }

            // Inserta la reserva ya como "paid"
            $cols = "camper_id,start_date,end_date,status,created_at,paid_at,stripe_session_id,stripe_payment_intent,total_cents,deposit_cents";
            $vals = "?,?,?,?,NOW(),NOW(),?,?,?,?";
            $args = [
                $metaCamperId ?: null,
                $metaStart ?: null,
                $metaEnd ?: null,
                'paid',
                $sessionId,
                (string)$session->payment_intent,
                $metaTotalCents ?: null,
                $metaDepoCents ?: null,
            ];
            if ($hasManage) { $cols .= ",manage_token"; $vals .= ",?"; $args[] = $manageToken; }
            if ($hasLang)   { $cols .= ",lang";         $vals .= ",?"; $args[] = preg_replace('/[^a-zA-Z_-]/','',$metaLang); }

            $ins = $pdo->prepare("INSERT INTO reservations ($cols) VALUES ($vals)");
            $ins->execute($args);
            $reservationId = (int)$pdo->lastInsertId();
        } else {
            // Si existía, la marcamos pagada y enlazamos PI
            $up = $pdo->prepare("
            UPDATE reservations
               SET status='paid',
                   stripe_payment_intent=:pi,
                   paid_at=COALESCE(paid_at, NOW())
             WHERE stripe_session_id=:ssid
             LIMIT 1
        ");
            $up->execute([':pi' => (string)$session->payment_intent, ':ssid' => $sessionId]);
        }

        // ========= 2) Vincular cliente (customers) =========
        $stripeCustomerId = null;
        $piLink = $session->payment_intent ?? null;

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
        if (!$stripeCustomerId && $piLink && !empty($piLink->customer)) {
            $stripeCustomerId = is_object($piLink->customer) ? ($piLink->customer->id ?? null) : (string)$piLink->customer;
        }

        $customerEmailNorm = '';
        foreach ($emailCands as $e) {
            if ($e && filter_var($e, FILTER_VALIDATE_EMAIL)) {
                $customerEmailNorm = norm($e);
                break;
            }
        }

        if ($customerEmailNorm) {
            $sel = $pdo->prepare("SELECT id, stripe_customer_id FROM customers WHERE email_norm=? LIMIT 1");
            $sel->execute([$customerEmailNorm]);

            if ($c = $sel->fetch(PDO::FETCH_ASSOC)) {
                $customerId = (int)$c['id'];
                if (!empty($stripeCustomerId) && empty($c['stripe_customer_id'])) {
                    $pdo->prepare("UPDATE customers SET stripe_customer_id=? WHERE id=? LIMIT 1")
                        ->execute([$stripeCustomerId, $customerId]);
                }
            } else {
                $full  = trim((string)($session->customer_details->name ?? ''));
                $parts = preg_split('/\s+/u', $full);
                $first = $parts[0] ?? '';
                $last  = $parts ? implode(' ', array_slice($parts, 1)) : '';

                $ins = $pdo->prepare("INSERT INTO customers (first_name,last_name,email,email_norm,stripe_customer_id,created_at)
                                  VALUES (?,?,?,?,?,NOW())");
                $ins->execute([$first, $last, $customerEmailNorm, $customerEmailNorm, $stripeCustomerId ?? null]);
                $customerId = (int)$pdo->lastInsertId();
            }

            $pdo->prepare("UPDATE reservations
              SET customer_id = COALESCE(customer_id, ?)
            WHERE id = ? LIMIT 1")
                ->execute([$customerId, $reservationId]);
        }

        // ========= 3) Intento rápido de localizar la Invoice =========
        $invoiceId = null; $invoicePdf = null; $invoiceUrl = null;
        $invoiceStatus = null; $invoicePaid = false;

        if (!empty($session->invoice)) {
            $invoiceId = is_object($session->invoice) ? $session->invoice->id : (string)$session->invoice;
        }
        $pi = $session->payment_intent ?? null;
        if (!$invoiceId && $pi && !empty($pi->invoice)) {
            $invoiceId = is_object($pi->invoice) ? $pi->invoice->id : (string)$pi->invoice;
        }
        if (!$invoiceId && $pi && !empty($pi->id)) {
            try {
                $list = \Stripe\Invoice::all(['limit' => 1, 'payment_intent' => $pi->id]);
                if (!empty($list->data[0])) { $invoiceId = $list->data[0]->id; }
            } catch (Throwable $e) { /* ignore */ }
        }
        if ($invoiceId) {
            for ($i=0; $i<4; $i++) { // 4 * 0.5s = 2s
                $inv = \Stripe\Invoice::retrieve($invoiceId);
                $invoicePdf    = $inv->invoice_pdf ?? null;
                $invoiceUrl    = $inv->hosted_invoice_url ?? null;
                $invoiceStatus = $inv->status ?? null;
                $invoicePaid   = ($invoiceStatus === 'paid');
                if ($invoicePdf || $invoiceUrl || $invoicePaid) { break; }
                usleep(500000);
            }
        }

        // Guarda en BD si existen columnas
        if ($invoiceId && columnExists($pdo, 'reservations', 'stripe_invoice_id')) {
            $pdo->prepare("UPDATE reservations SET stripe_invoice_id=:iid WHERE id=:rid LIMIT 1")
                ->execute([':iid'=>$invoiceId, ':rid'=>$reservationId]);
        }
        if (($invoicePdf || $invoiceUrl)
            && columnExists($pdo,'reservations','stripe_invoice_pdf')
            && columnExists($pdo,'reservations','stripe_invoice_url')) {
            $pdo->prepare("UPDATE reservations
                          SET stripe_invoice_pdf=:pdf, stripe_invoice_url=:url
                        WHERE id=:rid LIMIT 1")
                ->execute([':pdf'=>$invoicePdf, ':url'=>$invoiceUrl, ':rid'=>$reservationId]);
        }

        // ========= 4) Cargar datos de la reserva (para página/email) =========
        $hasManage = columnExists($pdo, 'reservations', 'manage_token');

        $select = "
        SELECT r.id, r.start_date, r.end_date,
               r.total_cents,
               c.name AS camper, c.price_per_night,
               DATEDIFF(r.end_date, r.start_date) AS nights";
        if ($hasManage) { $select .= ", r.manage_token"; }
        $select .= "
          FROM reservations r
          JOIN campers c ON c.id = r.camper_id
         WHERE r.id = :rid
         LIMIT 1";
        $st = $pdo->prepare($select);
        $st->execute([':rid' => $reservationId]);
        $res = $st->fetch(PDO::FETCH_ASSOC);
        if (!$res) { throw new Exception('Reservation not found after payment.'); }

        $nights = (int)$res['nights'];
        $baseUnit = (float)$res['price_per_night'];
        $totalCentsDb = (int)($res['total_cents'] ?? 0);

        // Usa total_cents si existe -> total y precio medio/noche
        if ($totalCentsDb > 0) {
            $total = $totalCentsDb / 100.0;
            $price = $nights > 0 ? $total / $nights : $baseUnit;
        } else {
            $price = $baseUnit;
            $total = $nights * $price;
        }

        // Manage URL (si existe manage_token)
        $manageUrl = null;
        if ($hasManage && !empty($res['manage_token'])) {
            $manageUrl = publicBaseUrl() . '/manage.php?rid='.(int)$res['id'].'&t='.$res['manage_token'];
        }

        // ========= 5) Email de confirmación (igual que tenías) =========
        $customerEmail = $session->customer_details->email ?? $session->customer_email ?? '';
        $customerName  = $session->customer_details->name  ?? '';

        $receiptUrl = null;
        try {
            $pi = $session->payment_intent ?? null; // ya viene expandido con charges
            if ($pi && isset($pi->charges->data[0])) {
                $receiptUrl = $pi->charges->data[0]->receipt_url ?? null;
            }
        } catch (Throwable $e) { /* ignore */ }

        // Fechas localizadas (fallback a formato simple)
        $startFmt = date('Y-m-d', strtotime($res['start_date']));
        $endFmt   = date('Y-m-d', strtotime($res['end_date']));

        $fmtDate = class_exists('IntlDateFormatter')
            ? new IntlDateFormatter(str_replace('_','-',$lang), IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE)
            : null;
        $startHuman = $fmtDate ? $fmtDate->format(new DateTime($res['start_date'])) : date('j M Y', strtotime($res['start_date']));
        $endHuman   = $fmtDate ? $fmtDate->format(new DateTime($res['end_date']))   : date('j M Y', strtotime($res['end_date']));

        // Moneda localizada
        $currency = isset($session->currency) ? strtoupper((string)$session->currency) : 'EUR';
        $fmtMoney = class_exists('NumberFormatter') ? new NumberFormatter(str_replace('_','-',$lang), NumberFormatter::CURRENCY) : null;
        $priceTxt = $fmtMoney ? $fmtMoney->formatCurrency((float)$price, $currency) : ('€'.number_format($price,2));
        $totalTxt = $fmtMoney ? $fmtMoney->formatCurrency((float)$total, $currency) : ('€'.number_format($total,2));

        // .ics (evento de día completo) para adjuntar en el email
        $endExclusive = (new DateTime($endFmt))->modify('+1 day')->format('Ymd');
        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR','VERSION:2.0','PRODID:-//Alisios Van//EN','BEGIN:VEVENT',
            'UID:' . (int)$res['id'] . '@alisiosvan',
            'DTSTAMP:' . gmdate('Ymd\THis\Z'),
            'DTSTART;VALUE=DATE:' . date('Ymd', strtotime($startFmt)),
            'DTEND;VALUE=DATE:' . $endExclusive,
            'SUMMARY:' . sprintf(__('Alisios Van · %s'), $res['camper']),
            'DESCRIPTION:' . __('Reservation confirmed'),
            'END:VEVENT','END:VCALENDAR'
        ]);

        // Textos localizados para email
        $tPaymentConfirmed = __('Payment confirmed');
        $tHeaderSub        = sprintf(__('Reservation #%d — Thank you for choosing Alisios Van'), (int)$res['id']);
        $tHi               = $customerName
            ? sprintf(__('Hi %s,'), htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8'))
            : __('Hi,');
        $tIntro            = __('Your reservation is <strong>confirmed</strong>. Here are your details:');
        $tCamper           = __('Camper');
        $tDates            = __('Dates');
        $tNights           = __('Nights');
        $tPriceNight       = __('Price/night');
        $tTotalPrice       = __('Total price');
        $tReceiptLabel     = __('Receipt');
        $tViewStripe       = __('View Stripe receipt');
        $tAttachIcs        = __('We\'ve attached a calendar file (.ics) so you can add the trip to your calendar.');
        $tQuestions        = sprintf(__('Questions? Write us at %s.'), '<a href="mailto:alisios.van@gmail.com" style="color:#5698A6;">alisios.van@gmail.com</a>');
        $tFooter           = __('Alisios Van · Canary Islands');

        // Plantilla email (resumen)
        $summaryTable = '
<table role="presentation" width="100%" cellspacing="0" cellpadding="0"
       style="border:1px solid rgba(0,0,0,0.06); border-radius:10px; background:#fbfbfb; margin-bottom:14px;">
  <tr><td style="padding:0">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
           style="font-family:Arial, sans-serif; font-size:14px; line-height:20px; mso-line-height-rule:exactly;">
      <tr>
        <td style="padding:10px 14px;">'.htmlspecialchars($tCamper,ENT_QUOTES,'UTF-8').'</td>
        <td align="right" style="padding:10px 14px;"><strong>'.htmlspecialchars($res['camper'],ENT_QUOTES,'UTF-8').'</strong></td>
      </tr>
      <tr>
        <td style="padding:10px 14px; border-top:1px dashed #DDD;">'.htmlspecialchars($tDates,ENT_QUOTES,'UTF-8').'</td>
        <td align="right" style="padding:10px 14px; border-top:1px dashed #DDD; white-space:nowrap;">'
            .htmlspecialchars($startHuman,ENT_QUOTES,'UTF-8').' &nbsp;&rarr;&nbsp; '
            .htmlspecialchars($endHuman,ENT_QUOTES,'UTF-8').'</td>
      </tr>
      <tr>
        <td style="padding:10px 14px; border-top:1px dashed #DDD;">'.htmlspecialchars($tNights,ENT_QUOTES,'UTF-8').'</td>
        <td align="right" style="padding:10px 14px; border-top:1px dashed #DDD;">'.$nights.'</td>
      </tr>
      <tr>
        <td style="padding:10px 14px; border-top:1px dashed #DDD;">'.htmlspecialchars($tPriceNight,ENT_QUOTES,'UTF-8').'</td>
        <td align="right" style="padding:10px 14px; border-top:1px dashed #DDD;">'.htmlspecialchars($priceTxt,ENT_QUOTES,'UTF-8').'</td>
      </tr>
      <tr>
        <td style="padding:10px 14px; border-top:1px dashed #DDD; font-weight:700;">'.htmlspecialchars($tTotalPrice,ENT_QUOTES,'UTF-8').'</td>
        <td align="right" style="padding:10px 14px; border-top:1px dashed #DDD; font-weight:700;">'.htmlspecialchars($totalTxt,ENT_QUOTES,'UTF-8').'</td>
      </tr>
    </table>
  </td></tr>
</table>';

        $html = '
<div style="font-family:Arial,sans-serif;line-height:1.55;color:#333;background:#e8e6e4;padding:24px;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
    <tr><td align="center">
      <table role="presentation" width="640" cellspacing="0" cellpadding="0"
             style="background:#FFFFFF;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,0.10);overflow:hidden;border:1px solid rgba(0,0,0,0.04);">
        <tr>
          <td style="background:#80C1D0;color:#fff;padding:18px 22px;">
            <div style="font-size:22px;font-weight:700;margin:0;">'.htmlspecialchars($tPaymentConfirmed,ENT_QUOTES,'UTF-8').'</div>
            <div style="opacity:.9;margin-top:4px;">'.htmlspecialchars($tHeaderSub,ENT_QUOTES,'UTF-8').'</div>
          </td>
        </tr>
        <tr>
          <td style="padding:20px 22px;">
            <p style="margin:0 0 8px 0">'.$tHi.'</p>
            <p style="margin:0 0 12px 0">'.$tIntro.'</p>
            '.$summaryTable.'
            '.($receiptUrl ? '<p style="margin:0 0 12px 0">'.htmlspecialchars($tReceiptLabel,ENT_QUOTES,'UTF-8').': <a href="'.htmlspecialchars($receiptUrl,ENT_QUOTES,'UTF-8').'" style="color:#5698A6;">'.htmlspecialchars($tViewStripe,ENT_QUOTES,'UTF-8').'</a></p>' : '').'
            <p style="margin:0 0 12px 0">'.$tAttachIcs.'</p>
            <p style="margin:0;color:#555">'.$tQuestions.'</p>
          </td>
        </tr>
        <tr>
          <td style="padding:12px 22px;background:#f7f7f7;color:#7D7D7D;font-size:12px;">'.htmlspecialchars($tFooter,ENT_QUOTES,'UTF-8').'</td>
        </tr>
      </table>
      '.(isset($manageUrl) && $manageUrl
                ? '<div style="max-width:640px;margin:0 auto;padding:12px 0 0 0;font-family:Arial,sans-serif;">
                 <p style="margin:12px 0">'.__('Manage your booking:').' 
                   <a href="'.htmlspecialchars($manageUrl,ENT_QUOTES,'UTF-8').'" style="color:#5698A6;">'.__('Open management page').'</a>
                 </p>
               </div>'
                : '').'
    </td></tr>
  </table>
</div>';

        $alt = sprintf(__("Payment confirmed\nReservation #%d\n"), (int)$res['id']).
            __("Camper").": {$res['camper']}\n".
            __("Dates").": {$startHuman} -> {$endHuman}\n".
            __("Nights").": {$nights}\n".
            __("Price/night").": {$priceTxt}\n".
            __("Total price").": {$totalTxt}\n".
            ($manageUrl ? __("Manage").": $manageUrl\n" : "");

        // SMTP config ---------------------------------------------------------
        $host      = env('SMTP_HOST', env('MAIL_HOST',''));
        $port      = (int) env('SMTP_PORT', env('MAIL_PORT', 587));
        $user      = env('SMTP_USER', env('MAIL_USER',''));
        $pass      = env('SMTP_PASS', env('MAIL_PASS',''));
        $secureRaw = strtolower((string) env('SMTP_SECURE','tls')); // ssl|tls
        $fromEmail = env('SMTP_FROM', env('MAIL_FROM', $user));
        $fromName  = env('SMTP_FROM_NAME', env('MAIL_FROM_NAME','Alisios Van'));
        $adminTo   = env('SMTP_TO', env('MAIL_BCC',''));

        // Helper cURL para PDF de invoice (si disponible)
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

        // **Enviar SIEMPRE** si no se ha enviado ya y hay SMTP y email destino
        $emailAlreadySent = false;
        if (columnExists($pdo, 'reservations', 'confirmation_email_sent_at')) {
            $q = $pdo->prepare("SELECT confirmation_email_sent_at FROM reservations WHERE stripe_session_id = :ssid LIMIT 1");
            $q->execute([':ssid' => $sessionId]);
            $emailAlreadySent = (bool)$q->fetchColumn();
        }

        $shouldSendEmail = (!$emailAlreadySent && $customerEmail && $host && $user && $pass);

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
                $mail->addCustomHeader('Content-Language', $lang);

                $mail->Subject = sprintf(__('Your reservation #%d is confirmed'), (int)$res['id']);
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
                    }
                }

                // Timeouts para no atascarse
                $mail->Timeout = 20;
                $mail->SMTPKeepAlive = false;

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
        }

        // ------------------ Página (para navegador) --------------------------
        ?>
        <!doctype html>
        <html lang="<?= htmlspecialchars($lang) ?>">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?= htmlspecialchars(__('Payment confirmed')) ?> | Alisios Van</title>

            <!-- evita traducción automática de Chrome -->
            <meta name="google" content="notranslate">

            <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
            <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flag-icons/css/flag-icons.min.css">

            <link rel="stylesheet" href="css/estilos.css">
            <link rel="stylesheet" href="css/cookies.css">
            <script src="js/cookies.js" defer></script>

            <style>
                .page-hero{ background-image:url('img/matcha-landing-page.jpeg'); }
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
                <h1 class="page-hero__title"><?= __('Payment confirmed!') ?></h1>
                <p class="mt-2">
                    <?= sprintf(__('Reservation #%d · Thank you for choosing Alisios Van'), (int)$res['id']) ?>
                </p>
            </div>
        </section>

        <main class="wrap">
            <div class="cardy mb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div class="flex-grow-1">
                        <h2 class="h4 mb-2"><?= __('Your trip') ?></h2>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="badge-soft"><i class="bi bi-truck"></i> <?= htmlspecialchars($res['camper'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="badge-soft"><i class="bi bi-moon-stars"></i> <?= sprintf(__('Nights: %d'), (int)$nights) ?></span>
                        </div>
                        <p class="mb-1">
                            <strong><?= __('Dates') ?>:</strong>
                            <?= htmlspecialchars($startHuman, ENT_QUOTES, 'UTF-8') ?> → <?= htmlspecialchars($endHuman, ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <p class="mb-3 text-muted"><?= __('We’ve emailed you the booking details and next steps.') ?></p>

                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn" href="index.php"><i class="bi bi-house-door"></i> <?= __('Back to Home') ?></a>
                            <a class="btn btn-outline-secondary" href="campers.php"><i class="bi bi-truck"></i> <?= __('Browse campers') ?></a>
                            <button id="btnIcs" class="btn btn-outline-secondary"><i class="bi bi-calendar-event"></i> <?= __('Add to calendar') ?></button>

                            <?php if ($invoicePdf): ?>
                                <a class="btn btn-outline-secondary" id="btnInvPdf" href="<?= htmlspecialchars($invoicePdf, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                                    <i class="bi bi-file-earmark-pdf"></i> <?= __('Download invoice (PDF)') ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($invoiceUrl): ?>
                                <a class="btn btn-outline-secondary" id="btnInvUrl" href="<?= htmlspecialchars($invoiceUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                                    <i class="bi bi-receipt"></i> <?= __('View invoice online') ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($manageUrl): ?>
                                <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($manageUrl, ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="bi bi-gear"></i> <?= __('Manage / cancel reservation') ?>
                                </a>
                            <?php endif; ?>
                            <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> <?= __('Print') ?></button>
                        </div>

                        <?php if (!$invoicePdf && !$invoiceUrl && !$invoiceId): ?>
                            <p class="text-muted mt-3 small">
                                <?= __('Your invoice is being generated. It can take a little while. We’ll email it to you and this page will update automatically when it’s ready.') ?>
                            </p>
                        <?php elseif (!$invoicePdf && !$invoiceUrl): ?>
                            <p class="text-muted mt-3 small">
                                <?= __('The invoice was created but the file isn’t ready yet. We’ll email it as soon as it’s available.') ?>
                            </p>
                        <?php endif; ?>

                        <p id="invMsg" class="text-muted small" style="display:none;"></p>

                        <script>
                            (function(){
                                const msg = document.getElementById('invMsg');
                                const params = new URLSearchParams(location.search);
                                const sid = params.get('session_id');
                                if (!sid) return;

                                async function check() {
                                    try {
                                        const r = await fetch('../api/invoice-status.php?session_id=' + encodeURIComponent(sid) + '&_ts=' + Date.now());
                                        const j = await r.json();
                                        if (j.ok && (j.invoice_pdf || j.invoice_url)) {
                                            const btnWrap = document.querySelector('.d-flex.flex-wrap.gap-2');
                                            if (!document.querySelector('#btnInvPdf') && j.invoice_pdf) {
                                                const a = document.createElement('a');
                                                a.id='btnInvPdf'; a.className='btn btn-outline-secondary';
                                                a.href=j.invoice_pdf; a.target='_blank'; a.rel='noopener';
                                                a.innerHTML='<i class="bi bi-file-earmark-pdf"></i> <?= addslashes(__('Download invoice (PDF)')) ?>';
                                                btnWrap.appendChild(a);
                                            }
                                            if (!document.querySelector('#btnInvUrl') && j.invoice_url) {
                                                const a = document.createElement('a');
                                                a.id='btnInvUrl'; a.className='btn btn-outline-secondary';
                                                a.href=j.invoice_url; a.target='_blank'; a.rel='noopener';
                                                a.innerHTML='<i class="bi bi-receipt"></i> <?= addslashes(__('View invoice online')) ?>';
                                                btnWrap.appendChild(a);
                                            }
                                            msg.style.display='none';
                                            return true;
                                        }
                                    } catch(e){}
                                    msg.style.display='block';
                                    msg.textContent = <?= json_encode(__('Your invoice is being generated. We will email it to you shortly.')) ?>;
                                    return false;
                                }

                                let tries = 0;
                                (async function loop(){
                                    const ok = await check();
                                    if (ok || ++tries > 12) return;
                                    setTimeout(loop, 10000);
                                })();
                            })();
                        </script>
                    </div>

                    <div style="min-width:260px;max-width:320px;" class="ms-auto">
                        <h3 class="h6 mb-2"><?= __('Payment') ?></h3>
                        <div class="summary">
                            <div class="rowx"><span><?= __('Price/night') ?></span><span>€<?= number_format((float)$price, 2) ?></span></div>
                            <div class="rowx"><span><?= __('Nights') ?></span><span><?= (int)$nights ?></span></div>
                            <div class="rowx total"><span><?= __('Total price') ?></span><span>€<?= number_format((float)$total, 2) ?></span></div>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-muted small">
                <?= sprintf(__('Questions? Email us at %s'), '<a href="mailto:alisios.van@gmail.com">alisios.van@gmail.com</a>') ?>
            </p>

            <!-- Data for ICS generation -->
            <div id="icsData"
                 data-title="<?= htmlspecialchars(sprintf(__('Alisios Van · %s'), $res['camper']), ENT_QUOTES, 'UTF-8') ?>"
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
                    const endD  = new Date(el.dataset.end); endD.setDate(endD.getDate() + 1);
                    const end   = ymd(endD.toISOString().slice(0,10));
                    const uid   = (el.dataset.id || '') + '@alisiosvan';
                    const dtstamp = new Date().toISOString().replace(/[-:]/g,'').split('.')[0] + 'Z';

                    return [
                        'BEGIN:VCALENDAR','VERSION:2.0','PRODID:-//Alisios Van//EN','METHOD:PUBLISH',
                        'BEGIN:VEVENT','UID:' + uid,'DTSTAMP:' + dtstamp,
                        'DTSTART;VALUE=DATE:' + start,'DTEND;VALUE=DATE:' + end,
                        'SUMMARY:' + title,
                        'DESCRIPTION:<?= addslashes(__('Reservation confirmed')) ?>',
                        'END:VEVENT','END:VCALENDAR'
                    ].join('\r\n');
                }

                function isIOS() { return /iP(hone|od|ad)/i.test(navigator.userAgent); }

                document.getElementById('btnIcs')?.addEventListener('click', () => {
                    const ics = buildIcs();

                    if (isIOS()) {
                        const dataUrl = 'data:text/calendar;charset=utf-8,' + encodeURIComponent(ics);
                        window.location.href = dataUrl;
                        return;
                    }
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
