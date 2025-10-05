<?php
declare(strict_types=1);

ini_set('display_errors','0');

require_once __DIR__ . '/../config/bootstrap_env.php';
require_once __DIR__ . '/../config/i18n-lite.php';
require_once __DIR__ . '/../config/db.php';

$pdo = get_pdo();

/* Utilidad: comprobar si existe una columna */
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

/* Si vienen parámetros antiguos, intenta limpiar un in_checkout seguro */
$rid = isset($_GET['rid']) ? (int)$_GET['rid'] : 0;
$tok = isset($_GET['t'])   ? (string)$_GET['t'] : '';
$hadPending = false;

if ($rid > 0) {
    try {
        $hasManage = columnExists($pdo, 'reservations', 'manage_token');
        if ($hasManage) {
            $st = $pdo->prepare("SELECT manage_token, status FROM reservations WHERE id=? LIMIT 1");
            $st->execute([$rid]);
            if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
                if (($row['status'] ?? '') === 'in_checkout' && ($row['manage_token'] ?? '') === $tok) {
                    $del = $pdo->prepare("DELETE FROM reservations WHERE id=? AND status='in_checkout' LIMIT 1");
                    $del->execute([$rid]);
                    $hadPending = $del->rowCount() > 0;
                }
            }
        } else {
            $del = $pdo->prepare("DELETE FROM reservations WHERE id=? AND status='in_checkout' LIMIT 1");
            $del->execute([$rid]);
            $hadPending = $del->rowCount() > 0;
        }
    } catch (Throwable $e) {
        error_log('cancel.php cleanup error: '.$e->getMessage());
    }
}

/* Render de página de cancelación (sin depender de rid) */
$lang = $GLOBALS['LANG'] ?? 'es';
?>
<!doctype html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="google" content="notranslate">
    <title><?= htmlspecialchars(__('Payment cancelled')) ?> | Alisios Van</title>
    <<!-- evita traducción automática de Chrome -->
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
        body{background:#f6f6f6}
        .wrap{max-width:820px;margin:40px auto;padding:20px}
        .cardy{background:#fff;border:1px solid rgba(0,0,0,.06);border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.08);padding:24px}
    </style>
</head>
<body>
<main class="wrap">
    <div class="cardy">
        <h1 class="h4 mb-2"><?= __('Payment cancelled') ?></h1>
        <p class="text-muted mb-3"><?= __('You went back from the payment. No charge has been made.') ?></p>
        <?php if ($hadPending): ?>
            <div class="alert alert-success small">
                <?= __('We removed the temporary reservation that was being created.') ?>
            </div>
        <?php endif; ?>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="index.php"><?= __('Back to Home') ?></a>
            <a class="btn btn-outline-secondary" href="campers.php"><?= __('Browse campers') ?></a>
        </div>
    </div>
</main>
</body>
</html>
