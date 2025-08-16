<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;

function respond(int $code, array $payload){ http_response_code($code); echo json_encode($payload); exit; }

// Cargar .env desde /env/.env
$root = dirname(__DIR__);                 // ruta a la carpeta raíz del proyecto
$dotenv = Dotenv::createImmutable($root . '/env');
$dotenv->safeLoad();

function envv(string $key, $default=null) {
    $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return ($val === false || $val === null || $val === '') ? $default : $val;
}

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['ok'=>false,'error'=>'Method not allowed']);

// CSRF
if (empty($_POST['csrf']) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
    respond(400, ['ok'=>false,'error'=>'Invalid CSRF token']);
}

// Honeypot
if (!empty($_POST['website'])) respond(200, ['ok'=>true]);

// Campos
$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$phone   = trim($_POST['phone']   ?? '');
$model   = trim($_POST['model']   ?? '');
$start   = trim($_POST['start']   ?? '');
$end     = trim($_POST['end']     ?? '');
$message = trim($_POST['message'] ?? '');
$privacy = isset($_POST['privacy']);

$errors = [];
if ($name === '')                               $errors[] = 'name';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'email';
if ($message === '')                            $errors[] = 'message';
if (!$privacy)                                  $errors[] = 'privacy';
if ($errors) respond(422, ['ok'=>false,'error'=>'Invalid fields','fields'=>$errors]);

// Cuerpo del email
$subject = 'Nueva solicitud desde la web — Alisios Van';
$lines = ['Nombre'=>$name,'Email'=>$email,'Teléfono'=>$phone,'Modelo'=>$model,'Desde'=>$start,'Hasta'=>$end];

$rows = '';
foreach ($lines as $k=>$v) {
    $rows .= '<tr><td style="padding:6px 10px;border:1px solid #eee;"><strong>'
        . htmlspecialchars($k).'</strong></td><td style="padding:6px 10px;border:1px solid #eee;">'
        . nl2br(htmlspecialchars($v)).'</td></tr>';
}
$htmlBody = '<div style="font-family:system-ui,Segoe UI,Arial,sans-serif">
  <h2>Nueva solicitud desde la web</h2>
  <table cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #eee">'.$rows.'</table>
  <h3 style="margin-top:16px">Mensaje</h3>
  <div style="white-space:pre-wrap">'.nl2br(htmlspecialchars($message)).'</div>
</div>';
$plainBody = "Nueva solicitud desde la web\n\n";
foreach ($lines as $k=>$v) { $plainBody .= "$k: $v\n"; }
$plainBody .= "\nMensaje:\n$message\n";

// Configurar PHPMailer con .env
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = envv('SMTP_HOST', 'smtp.gmail.com');
    $mail->SMTPAuth   = true;
    $mail->Username   = envv('SMTP_USER');
    $mail->Password   = envv('SMTP_PASS');

    $secure = strtolower((string)envv('SMTP_SECURE', 'tls'));
    if ($secure === 'ssl' || $secure === 'smtps') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = (int)envv('SMTP_PORT', 465);
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)envv('SMTP_PORT', 587);
    }

    $fromEmail = envv('SMTP_FROM', envv('SMTP_USER'));
    $fromName  = envv('SMTP_FROM_NAME', 'Alisios Van Web');
    $toEmail   = envv('SMTP_TO', envv('SMTP_USER'));

    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($toEmail, 'Alisios Van');
    $mail->addReplyTo($email, $name);

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $htmlBody;
    $mail->AltBody = $plainBody;

    $mail->send();
    respond(200, ['ok'=>true]);
} catch (Throwable $e) {
    // En pruebas, si quieres ver el error exacto, activa debug:
    // $mail->SMTPDebug = 2; $mail->Debugoutput = 'html';
    respond(500, ['ok'=>false,'error'=>'Could not send email']);
}
