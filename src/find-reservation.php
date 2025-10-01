<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require_once __DIR__ . '/../config/bootstrap_env.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/i18n-lite.php'; // carga diccionarios
$pdo = get_pdo();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;
require_once __DIR__ . '/../vendor/autoload.php';

/* ===== Helpers ===== */
function norm($s){ return mb_strtolower(trim((string)$s)); }

function columnExists(PDO $pdo, string $table, string $column): bool {
    $sql = "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([':t'=>$table, ':c'=>$column]);
    return (bool)$st->fetchColumn();
}

/** Enviar correo con enlace de gestión */
function send_manage_link(string $toEmail, int $rid, string $token): bool {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base   = rtrim(env('PUBLIC_BASE_URL', "$scheme://$host/src"), '/');
    $url    = $base . '/manage.php?rid='.(int)$rid.'&t='.urlencode($token);

    $subject = __('find.mail.subject');
    $intro   = __('find.mail.intro');
    $ignore  = __('find.mail.ignore');
    $footer  = 'Alisios Van';

    $bodyHtml = '
    <div style="font-family:Arial,sans-serif;background:#f8f9fa;padding:20px;">
      <table role="presentation" cellspacing="0" cellpadding="0" 
        style="max-width:600px;margin:auto;background:#fff;border-radius:10px;overflow:hidden;
               box-shadow:0 6px 20px rgba(0,0,0,.08);">
        <tr>
          <td style="background:#80C1D0;color:#fff;padding:18px 22px;font-size:20px;font-weight:bold;">'
        .htmlspecialchars($subject).'</td>
        </tr>
        <tr>
          <td style="padding:22px;">
            <p>'.$intro.'</p>
            <p style="margin:20px 0;">
              <a href="'.htmlspecialchars($url).'" 
                 style="background:#80C1D0;color:#fff;padding:10px 18px;border-radius:6px;
                        text-decoration:none;font-weight:bold;">'.__('find.mail.button').'</a>
            </p>
            <p>'.$ignore.'</p>
            <p style="margin-top:30px;color:#666;font-size:12px;">'.$footer.'</p>
          </td>
        </tr>
      </table>
    </div>';

    $bodyTxt = $intro."\n\n".$url."\n\n".$ignore."\n\n".$footer;

    // SMTP config
    $hostSmtp  = env('SMTP_HOST', env('MAIL_HOST',''));
    $port      = (int) env('SMTP_PORT', env('MAIL_PORT', 587));
    $user      = env('SMTP_USER', env('MAIL_USER',''));
    $pass      = env('SMTP_PASS', env('MAIL_PASS',''));
    $secureRaw = strtolower((string) env('SMTP_SECURE','tls'));
    $fromEmail = env('SMTP_FROM', env('MAIL_FROM', $user));
    $fromName  = env('SMTP_FROM_NAME', env('MAIL_FROM_NAME','Alisios Van'));
    $replyTo   = 'alisios.van@gmail.com';
    $bccAdmin  = env('SMTP_TO', env('MAIL_BCC',''));

    if (!$hostSmtp || !$user || !$pass || !$toEmail) {
        error_log('send_manage_link: SMTP config missing or invalid recipient');
        return false;
    }

    try {
        $mail = new PHPMailer(true);
        $mail->CharSet  = 'UTF-8';
        $mail->Encoding = 'quoted-printable';
        $mail->isSMTP();
        $mail->Host       = $hostSmtp;
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
        $mail->addAddress($toEmail);
        if (!empty($bccAdmin)) $mail->addBCC($bccAdmin);
        $mail->addReplyTo($replyTo, 'Alisios Van');

        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body    = $bodyHtml;
        $mail->AltBody = $bodyTxt;

        $mail->Timeout = 20;
        $mail->SMTPKeepAlive = false;

        return $mail->send();
    } catch (MailException $e) {
        error_log('send_manage_link mail error: '.$e->getMessage());
        return false;
    }
}

/* ===================== entrada ===================== */
$rid        = (int)($_GET['rid']   ?? $_POST['rid']   ?? 0);
$emailInput = (string)($_GET['email'] ?? $_POST['email'] ?? '');
$email      = norm($emailInput);

$sent = false;
try {
    if ($rid > 0 && $email !== '') {
        $row = null;

        $canJoinCustomers = columnExists($pdo, 'reservations', 'customer_id')
            && columnExists($pdo, 'customers', 'id')
            && columnExists($pdo, 'customers', 'email');

        $conds  = [];
        $params = [$rid];
        $join   = $canJoinCustomers ? "LEFT JOIN customers cu ON cu.id = r.customer_id" : "";

        if (columnExists($pdo, 'reservations', 'customer_email')) {
            $conds[]  = "LOWER(TRIM(r.customer_email)) = LOWER(?)";
            $params[] = $email;
        }
        if (columnExists($pdo, 'reservations', 'email')) {
            $conds[]  = "LOWER(TRIM(r.email)) = LOWER(?)";
            $params[] = $email;
        }
        if ($canJoinCustomers) {
            $conds[]  = "LOWER(TRIM(cu.email)) = LOWER(?)";
            $params[] = $email;
        }

        if ($conds) {
            $sql = "SELECT r.id, r.manage_token FROM reservations r $join
                    WHERE r.id = ? AND (".implode(' OR ', $conds).") LIMIT 1";
            $st = $pdo->prepare($sql);
            $st->execute($params);
            $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        if ($row) {
            if (empty($row['manage_token'])) {
                $tok = bin2hex(random_bytes(16));
                $pdo->prepare("UPDATE reservations SET manage_token=? WHERE id=? LIMIT 1")
                    ->execute([$tok, $rid]);
                $row['manage_token'] = $tok;
            }
            $sent = send_manage_link($emailInput, $rid, $row['manage_token']);
        }
    }
} catch (Throwable $e) {
    error_log("find-reservation error: ".$e->getMessage());
}

/* ===================== vista ===================== */
?>
<!doctype html>
<html lang="<?= htmlspecialchars($GLOBALS['LANG'] ?? 'en') ?>">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __('find.title') ?></title>
    <!-- evita traducción automática de Chrome -->
    <meta name="google" content="notranslate">

    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" defer></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/manage.css">
    <link rel="stylesheet" href="css/cookies.css">
    <script src="js/header.js" defer></script>
    <script src="js/cookies.js" defer></script>
</head>
<body>
<?php include 'inc/header.inc'; ?>

<section class="page-hero manage-hero">
    <div class="page-hero__content">
        <h1 class="page-hero__title"><?= __('find.title') ?></h1>
        <p class="mt-2"><?= __('find.subtitle') ?></p>
    </div>
</section>

<main class="container py-5" style="max-width:720px;">
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['rid'])): ?>
        <div class="alert alert-info mb-4">
            <?= __('find.alert.sent') ?>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-house"></i> <?= __('find.btn.home') ?>
            </a>
            <a href="find-reservation.php" class="btn btn-primary">
                <i class="bi bi-search"></i> <?= __('find.btn.retry') ?>
            </a>
        </div>
    <?php else: ?>
        <form method="get" class="card card-body shadow-sm">
            <h1 class="h5 mb-3"><?= __('find.title') ?></h1>
            <div class="mb-3">
                <label class="form-label"><?= __('find.form.resid') ?></label>
                <input type="number" class="form-control" name="rid" required>
            </div>
            <div class="mb-3">
                <label class="form-label"><?= __('find.form.email') ?></label>
                <input type="email" class="form-control" name="email" required>
            </div>
            <button type="submit" class="btn btn-primary"><?= __('find.form.send') ?></button>
        </form>
    <?php endif; ?>
</main>

<?php include 'inc/footer.inc'; ?>
</body>
</html>
