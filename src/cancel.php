<?php
declare(strict_types=1);

ini_set('display_errors','0');

require_once __DIR__ . '/../config/bootstrap_env.php';
require_once __DIR__ . '/../config/i18n-lite.php';
require_once __DIR__ . '/../config/db.php';

$pdo = get_pdo();

/** Comprueba si existe una columna */
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

$lang = $GLOBALS['LANG'] ?? 'es';
?>
<!doctype html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(__('Payment cancelled')) ?> | Alisios Van</title>

    <!-- evita traducción automática de Chrome -->
    <meta name="google" content="notranslate">

    <!-- Tipografías y estilos externos (igual que thanks.php) -->
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flag-icons/css/flag-icons.min.css">

    <!-- Tus hojas de estilo -->
    <link rel="stylesheet" href="/src/css/estilos.css">
    <link rel="stylesheet" href="/src/css/cookies.css">
    <script src="/src/js/cookies.js" defer></script>

    <style>
        /* Mismos estilos base que en thanks.php */
        .page-hero{ background-image:url('/src/img/matcha-landing-page.jpeg'); }
        .wrap{ max-width: 1000px; margin-inline:auto; padding: var(--spacing-l); }
        .cardy{
            background: var(--color-blanco);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow-medium);
            border: 1px solid rgba(0,0,0,.04);
            padding: clamp(1rem, 2vw, 1.5rem);
        }
        .state{
            display:flex; align-items:center; gap:.75rem;
            background: rgba(255,255,255,.6);
            border:1px solid rgba(0,0,0,.06);
            border-radius: 12px;
            padding:.75rem 1rem;
        }
        .state i{
            font-size: 1.4rem;
            color: var(--color-blanco);
            width: 36px; height:36px; display:grid; place-items:center; border-radius:999px;
        }
        .state i.warn{ background: var(--color-atardecer); }
        .state i.ok{ background: var(--color-mar); }
    </style>
</head>
<body>
<section class="page-hero">
    <div class="page-hero__content">
        <h1 class="page-hero__title"><?= __('Payment cancelled') ?></h1>
        <p class="mt-2">
            <?= __('You went back from the payment. No charge has been made.') ?>
        </p>
    </div>
</section>

<main class="wrap">
    <div class="cardy">
        <div class="state mb-3">
            <i class="bi bi-x-circle warn"></i>
            <div class="flex-grow-1">
                <strong><?= __('Payment was cancelled') ?></strong>
                <div class="text-muted"><?= __('No charge has been made and your booking has not been confirmed.') ?></div>
            </div>
        </div>

        <?php if ($hadPending): ?>
            <div class="alert alert-success small">
                <?= __('We removed the temporary reservation that was being created.') ?>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-wrap gap-2">
            <a class="btn" href="/<?= htmlspecialchars($lang) ?>/">
                <i class="bi bi-house-door"></i> <?= __('Back to Home') ?>
            </a>
            <a class="btn btn-outline-secondary" href="/<?= htmlspecialchars($lang) ?>/campers/">
                <i class="bi bi-truck"></i> <?= __('Browse campers') ?>
            </a>
            <a class="btn btn-outline-secondary" href="/<?= htmlspecialchars($lang) ?>/contacto/">
                <i class="bi bi-envelope"></i> <?= __('Contact us') ?>
            </a>

            <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> <?= __('Print') ?></button>
        </div>

        <p class="text-muted small mt-3">
            <?= sprintf(__('Questions? Email us at %s'), '<a href="mailto:alisios.van@gmail.com">alisios.van@gmail.com</a>') ?>
        </p>
    </div>
</main>
</body>
</html>
