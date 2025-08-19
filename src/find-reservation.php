<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

// Carga Composer + .env desde /secure
require_once __DIR__ . '/../config/bootstrap_env.php';

require __DIR__ . '/../config/db.php';
$pdo = get_pdo();


// (Opcional) acceso admin con ?key=... como ya tenías
function admin_key(): ?string {
    $envKey = env('ADMIN_KEY','');
    if (!$envKey) return null;

    $k = $_GET['key'] ?? ($_COOKIE['admin_key'] ?? '');
    if ($k && hash_equals($envKey, (string)$k)) {
        if (empty($_COOKIE['admin_key'])) {
            // cookie persistente y segura
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

function norm($s){ return mb_strtolower(trim((string)$s)); }

$rid   = (int)($_GET['rid'] ?? 0);
$email = norm($_GET['email'] ?? '');
$isAdmin = admin_key() !== null;

if (!$rid || (!$isAdmin && $email==='')) {
    http_response_code(400);
    echo !$rid ? 'Missing reservation number.' : 'Email is required.'; exit;
}

$row = null;
if ($isAdmin) {
    $st = $pdo->prepare("SELECT id, manage_token FROM reservations WHERE id=? LIMIT 1");
    $st->execute([$rid]);
    $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
} else {
    // usamos customers.email_norm que mantienen los triggers
    $st = $pdo->prepare("
    SELECT r.id, r.manage_token
    FROM reservations r
    JOIN customers c ON c.id = r.customer_id
    WHERE r.id=? AND c.email_norm=? LIMIT 1
  ");
    $st->execute([$rid, $email]);
    $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

if (!$row) {
    // vista amable
    ?>
    <!doctype html><html lang="en"><head>
        <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Find Reservation</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="css/estilos.css"><link rel="stylesheet" href="css/header.css"><link rel="stylesheet" href="css/cookies.css">
        <style>:root{ --header-bg-rgb: 82,118,159; }</style>
    </head><body>
    <?php include 'inc/header.inc'; ?>
    <section class="page-hero"><div class="page-hero__content"><h1 class="page-hero__title">Find your reservation</h1></div></section>
    <main class="container py-4" style="max-width:720px;">
        <div class="alert alert-warning">We couldn’t find a reservation with that combination. Please check your Reservation # and email.</div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#manageBookingModal">Try again</button>
    </main>
    <?php include 'inc/footer.inc'; ?>
    </body></html>
    <?php
    exit;
}

if (empty($row['manage_token'])) {
    $tok = bin2hex(random_bytes(16));
    $pdo->prepare("UPDATE reservations SET manage_token=? WHERE id=? LIMIT 1")->execute([$tok, $rid]);
    $row['manage_token'] = $tok;
}

$qs = http_build_query(['rid'=>$rid,'t'=>$row['manage_token']]);
header('Location: manage.php?'.$qs);
exit;
