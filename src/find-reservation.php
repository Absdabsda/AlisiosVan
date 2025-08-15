<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__.'/../env')->safeLoad();
require __DIR__ . '/../config/db.php';
$pdo = get_pdo();

use Stripe\Stripe;
use Stripe\Checkout\Session as CheckoutSession;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

Stripe::setApiKey($_ENV['STRIPE_SECRET']);

function columnExists(PDO $pdo, string $table, string $column): bool {
    $st=$pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?"); $st->execute([$column]); return (bool)$st->fetch();
}
function publicBaseUrl(): string {
    $env = rtrim($_ENV['PUBLIC_BASE_URL'] ?? '', '/');
    if ($env) return $env;
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return rtrim("$scheme://$host$dir", '/');
}

$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $rid   = (int)($_POST['rid'] ?? 0);
    $email = trim((string)($_POST['email'] ?? ''));

    if ($rid && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Carga reserva
        $st = $pdo->prepare("SELECT id, stripe_session_id, manage_token FROM reservations WHERE id=? LIMIT 1");
        $st->execute([$rid]);
        $r = $st->fetch(PDO::FETCH_ASSOC);

        if ($r && !empty($r['stripe_session_id'])) {
            // Email del cliente desde Stripe
            $sess = CheckoutSession::retrieve($r['stripe_session_id'], ['expand'=>['customer','customer_details']]);
            $custEmail = $sess->customer_details->email ?? $sess->customer_email ?? '';

            if ($custEmail && strcasecmp($custEmail, $email)===0) {
                // Enlace de gestión
                if (!columnExists($pdo,'reservations','manage_token') || empty($r['manage_token'])) {
                    $msg = 'Tu reserva no tiene enlace de gestión asociado todavía. Escríbenos por email 😊';
                } else {
                    $manageUrl = publicBaseUrl().'/manage.php?rid='.$r['id'].'&t='.$r['manage_token'];

                    // Enviamos el enlace por correo
                    $host = $_ENV['SMTP_HOST'] ?? $_ENV['MAIL_HOST'] ?? '';
                    $port = (int)($_ENV['SMTP_PORT'] ?? $_ENV['MAIL_PORT'] ?? 587);
                    $user = $_ENV['SMTP_USER'] ?? $_ENV['MAIL_USER'] ?? '';
                    $pass = $_ENV['SMTP_PASS'] ?? $_ENV['MAIL_PASS'] ?? '';
                    $from = $_ENV['SMTP_FROM'] ?? $_ENV['MAIL_FROM'] ?? $user;
                    $fromName = $_ENV['SMTP_FROM_NAME'] ?? $_ENV['MAIL_FROM_NAME'] ?? 'Alisios Van';

                    if ($host && $user && $pass) {
                        try {
                            $mail = new PHPMailer(true);
                            $mail->CharSet='UTF-8'; $mail->Encoding='quoted-printable';
                            $mail->isSMTP(); $mail->Host=$host; $mail->SMTPAuth=true; $mail->Username=$user; $mail->Password=$pass;
                            if ($port===465 || (($_ENV['SMTP_SECURE'] ?? 'tls')==='ssl')) {
                                $mail->SMTPSecure=PHPMailer::ENCRYPTION_SMTPS; $mail->Port=$port ?: 465;
                            } else {
                                $mail->SMTPSecure=PHPMailer::ENCRYPTION_STARTTLS; $mail->Port=$port ?: 587;
                            }
                            $mail->setFrom($from, $fromName);
                            $mail->addAddress($custEmail);
                            $mail->Subject = "Manage your reservation #{$r['id']}";
                            $mail->isHTML(true);
                            $mail->Body = '<p>Here is your link to manage your booking:</p><p><a href="'.htmlspecialchars($manageUrl,ENT_QUOTES,'UTF-8').'">'.$manageUrl.'</a></p>';
                            $mail->AltBody = "Manage link: $manageUrl";
                            $mail->send();
                            $msg = 'Te hemos enviado un correo con tu enlace de gestión.';
                        } catch (MailException $e) {
                            $msg = 'No se pudo enviar el correo. Escríbenos, por favor.';
                            error_log('find-reservation mail error: '.$e->getMessage());
                        }
                    } else {
                        $msg = 'Correo no configurado. Contacta con nosotros, por favor.';
                    }
                }
            } else {
                $msg = 'No coincide el email con la reserva.';
            }
        } else {
            $msg = 'Reserva no encontrada.';
        }
    } else {
        $msg = 'Introduce un número de reserva y un email válidos.';
    }
}
?>
<!doctype html><html lang="es"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Encontrar reserva</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="p-4">
<div class="container" style="max-width:560px">
    <h1 class="h4 mb-3">Gestionar mi reserva</h1>
    <p class="text-muted">Introduce tu nº de reserva y tu email. Te enviaremos un enlace para gestionarla.</p>
    <?php if($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <form method="post" class="card p-3">
        <div class="mb-3">
            <label class="form-label">Nº de reserva</label>
            <input name="rid" type="number" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input name="email" type="email" class="form-control" required>
        </div>
        <button class="btn btn-primary">Enviar enlace</button>
    </form>
</div>
</body></html>
