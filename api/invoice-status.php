<?php
declare(strict_types=1);
ini_set('display_errors','0');

require_once __DIR__ . '/../../config/bootstrap_env.php';
require_once __DIR__ . '/../../config/db.php';

use Stripe\Stripe;
use Stripe\Checkout\Session as CheckoutSession;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

$pdo = get_pdo();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// -------- Helpers ----------------------------------------------------------
function columnExists(PDO $pdo, string $table, string $column): bool {
    $sql = "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = :t
                AND COLUMN_NAME = :c
              LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([':t'=>$table, ':c'=>$column]);
    return (bool)$st->fetchColumn();
}

function fetchUrl(string $url): ?string {
    if (!$url) return null;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $data = curl_exec($ch);
    $ok = ($data !== false) && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
    curl_close($ch);
    return $ok ? $data : null;
}

// -------- Stripe init ------------------------------------------------------
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
    // Sesión + invoice
    $session = CheckoutSession::retrieve($sessionId, [
        'expand'=>['invoice','payment_intent','customer']
    ]);

    $invoiceId = null; $invoicePdf = null; $invoiceUrl = null; $status = null;

    if (!empty($session->invoice)) {
        $inv = is_object($session->invoice) ? $session->invoice : \Stripe\Invoice::retrieve($session->invoice);
        $invoiceId  = $inv->id ?? null;
        $invoicePdf = $inv->invoice_pdf ?? null;
        $invoiceUrl = $inv->hosted_invoice_url ?? null;
        $status     = $inv->status ?? null;
    }

    // ===== Guardar en BD ===================================================
    if ($invoiceId && columnExists($pdo,'reservations','stripe_invoice_id')) {
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

    // ===== Enviar email si no se envió aún =================================
    if (($invoicePdf || $invoiceUrl) && columnExists($pdo,'reservations','invoice_email_sent_at')) {
        $q = $pdo->prepare("SELECT r.id, r.invoice_email_sent_at, c.email, c.first_name, c.last_name
                              FROM reservations r
                              JOIN customers c ON c.id = r.customer_id
                             WHERE r.stripe_session_id = :ssid LIMIT 1");
        $q->execute([':ssid'=>$sessionId]);
        $row = $q->fetch(PDO::FETCH_ASSOC);

        if ($row && empty($row['invoice_email_sent_at']) && !empty($row['email'])) {
            try {
                $mail = new PHPMailer(true);
                $mail->CharSet  = 'UTF-8';
                $mail->Encoding = 'quoted-printable';
                $mail->isSMTP();
                $mail->Host       = env('SMTP_HOST');
                $mail->SMTPAuth   = true;
                $mail->Username   = env('SMTP_USER');
                $mail->Password   = env('SMTP_PASS');
                $mail->SMTPSecure = env('SMTP_SECURE','tls');
                $mail->Port       = (int)env('SMTP_PORT',587);

                $mail->setFrom(env('SMTP_FROM', env('MAIL_FROM', env('SMTP_USER'))), env('SMTP_FROM_NAME','Alisios Van'));
                $mail->addAddress($row['email'], trim(($row['first_name'] ?? '').' '.($row['last_name'] ?? '')));
                $adminTo = env('SMTP_TO', env('MAIL_BCC',''));
                if ($adminTo) $mail->addBCC($adminTo);
                $mail->addReplyTo('alisios.van@gmail.com','Alisios Van');

                $mail->Subject = "Invoice for your reservation #".$row['id'];
                $html = '<p>Dear customer,</p>
                         <p>Your invoice for reservation #'.(int)$row['id'].' is ready.</p>'.
                    ($invoiceUrl ? '<p><a href="'.htmlspecialchars($invoiceUrl,ENT_QUOTES,'UTF-8').'">View invoice online</a></p>' : '').
                    '<p>Thank you for choosing Alisios Van.</p>';
                $mail->isHTML(true);
                $mail->Body    = $html;
                $mail->AltBody = "Your invoice for reservation #".$row['id']."\n".$invoiceUrl;

                if ($invoicePdf) {
                    $pdfData = fetchUrl($invoicePdf);
                    if ($pdfData) {
                        $mail->addStringAttachment($pdfData, "Invoice-{$row['id']}.pdf", 'base64', 'application/pdf');
                    }
                }

                $mail->send();

                $pdo->prepare("UPDATE reservations
                                  SET invoice_email_sent_at = NOW()
                                WHERE stripe_session_id=:ssid")->execute([':ssid'=>$sessionId]);

            } catch (MailException $e) {
                error_log("Invoice mail error: ".$e->getMessage());
            }
        }
    }

    // ===== Devolver JSON ===================================================
    echo json_encode([
        'ok'          => true,
        'invoice_id'  => $invoiceId,
        'invoice_pdf' => $invoicePdf,
        'invoice_url' => $invoiceUrl,
        'status'      => $status,
    ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
