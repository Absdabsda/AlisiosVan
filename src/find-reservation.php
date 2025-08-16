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
    $st = $pdo->prepare("
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = :t AND COLUMN_NAME = :c
     LIMIT 1
  ");
    $st->execute([':t'=>$table, ':c'=>$column]);
    return (bool)$st->fetchColumn();
}
function publicBaseUrl(): string {
    $env = rtrim($_ENV['PUBLIC_BASE_URL'] ?? '', '/');
    if ($env) return $env;
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return rtrim("$scheme://$host$dir", '/');
}

$msg = ''; $msgType = 'info';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $rid   = (int)($_POST['rid'] ?? 0);
    $email = trim((string)($_POST['email'] ?? ''));

    if ($rid && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $st = $pdo->prepare("SELECT id, stripe_session_id, manage_token FROM reservations WHERE id=? LIMIT 1");
        $st->execute([$rid]);
        $r = $st->fetch(PDO::FETCH_ASSOC);

        if ($r && !empty($r['stripe_session_id'])) {
            $sess = CheckoutSession::retrieve($r['stripe_session_id'], ['expand'=>['customer','customer_details']]);
            $custEmail = $sess->customer_details->email ?? $sess->customer_email ?? '';

            if ($custEmail && strcasecmp($custEmail, $email)===0) {
                if (!columnExists($pdo,'reservations','manage_token') || empty($r['manage_token'])) {
                    $msg='Tu reserva todavía no tiene enlace de gestión. Escríbenos por email 😊'; $msgType='warning';
                } else {
                    $manageUrl = publicBaseUrl().'/manage.php?rid='.$r['id'].'&t='.$r['manage_token'];

                    // Enviamos el enlace
                    $host=$_ENV['SMTP_HOST'] ?? $_ENV['MAIL_HOST'] ?? '';
                    $port=(int)($_ENV['SMTP_PORT'] ?? $_ENV['MAIL_PORT'] ?? 587);
                    $user=$_ENV['SMTP_USER'] ?? $_ENV['MAIL_USER'] ?? ''; $pass=$_ENV['SMTP_PASS'] ?? $_ENV['MAIL_PASS'] ?? '';
                    $from=$_ENV['SMTP_FROM'] ?? $_ENV['MAIL_FROM'] ?? $user; $fromName=$_ENV['SMTP_FROM_NAME'] ?? $_ENV['MAIL_FROM_NAME'] ?? 'Alisios Van';

                    if ($host && $user && $pass) {
                        try {
                            $mail=new PHPMailer(true);
                            $mail->CharSet='UTF-8'; $mail->Encoding='quoted-printable';
                            $mail->isSMTP(); $mail->Host=$host; $mail->SMTPAuth=true; $mail->Username=$user; $mail->Password=$pass;
                            if ($port===465 || (($_ENV['SMTP_SECURE'] ?? 'tls')==='ssl')) { $mail->SMTPSecure=PHPMailer::ENCRYPTION_SMTPS; $mail->Port=$port ?: 465; }
                            else { $mail->SMTPSecure=PHPMailer::ENCRYPTION_STARTTLS; $mail->Port=$port ?: 587; }
                            $mail->setFrom($from,$fromName);
                            $mail->addAddress($custEmail);
                            $mail->Subject="Manage your reservation #{$r['id']}";
                            $mail->isHTML(true);
                            $mail->Body='<p>Here is your link to manage your booking:</p><p><a href="'.htmlspecialchars($manageUrl,ENT_QUOTES,'UTF-8').'">'.$manageUrl.'</a></p>';
                            $mail->AltBody="Manage link: $manageUrl";
                            $mail->send();
                            $msg='Te hemos enviado un correo con tu enlace de gestión.'; $msgType='success';
                        } catch (MailException $e) {
                            error_log('find-reservation mail error: '.$e->getMessage());
                            $msg='No se pudo enviar el correo. Escríbenos, por favor.'; $msgType='danger';
                        }
                    } else {
                        $msg='Correo no configurado. Contacta con nosotros, por favor.'; $msgType='danger';
                    }
                }
            } else { $msg='No coincide el email con la reserva.'; $msgType='danger'; }
        } else { $msg='Reserva no encontrada.'; $msgType='danger'; }
    } else { $msg='Introduce un número de reserva y un email válidos.'; $msgType='warning'; }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestionar mi reserva | Alisios Van</title>

    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/cookies.css">
    <script src="js/cookies.js" defer></script>

</head>
<body>
<?php include 'inc/header.inc'; ?>

<section class="page-hero" style="background-image:url('img/landing-matcha.02.31.jpeg')">
    <div class="page-hero__content">
        <h1 class="page-hero__title">Manage your booking</h1>
        <p class="mt-2">Encuentra tu reserva y recibe tu enlace seguro</p>
    </div>
</section>

<main class="wrap">
    <div class="cardy mb-3">
        <h2 class="h4 mb-3">Buscar reserva</h2>
        <p class="text-muted">Introduce tu <strong>nº de reserva</strong> y tu <strong>email</strong>. Te enviaremos un enlace para gestionar/cancelar.</p>

        <?php if($msg): ?>
            <div class="alert alert-<?= htmlspecialchars($msgType) ?>"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <form method="post" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Nº de reserva</label>
                <input name="rid" type="number" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input name="email" type="email" class="form-control" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn w-100"><i class="bi bi-envelope-check"></i> Enviar enlace</button>
            </div>
        </form>
    </div>

    <p class="text-muted small">¿Dudas? Escríbenos a <a href="mailto:alisios.van@gmail.com">alisios.van@gmail.com</a>.</p>
</main>

<?php include 'inc/footer.inc'; ?>
</body>
</html>
