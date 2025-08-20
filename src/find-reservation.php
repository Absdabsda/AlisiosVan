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
    $row = null; // fuerza la vista con el modal (igual que "no encontrado")
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
        <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" defer></script>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

        <link rel="stylesheet" href="css/estilos.css">
        <link rel="stylesheet" href="css/header.css">
        <link rel="stylesheet" href="css/manage.css">
        <link rel="stylesheet" href="css/cookies.css">

        <script src="js/header.js" defer></script>
        <script src="js/cookies.js" defer></script>

        <style>
            :root { --header-bg-rgb: 84,70,62; } /* #54463E */
        </style>
    </head><body>
    <?php include 'inc/header.inc'; ?>
    <section class="page-hero manage-hero"><div class="page-hero__content"><h1 class="page-hero__title">Find your reservation</h1></div></section>
    <main class="container py-4" style="max-width:720px;">
        <div class="alert alert-warning">We couldn’t find a reservation with that combination. Please check your Reservation # and email.</div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#manageBookingModal">Try again</button>
    </main>
    <div class="modal fade" id="manageBookingModal" tabindex="-1" aria-labelledby="manageBookingLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="get" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="manageBookingLabel">Find your reservation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Reservation #</label>
                            <input type="number" class="form-control" name="rid" value="<?= (int)$rid ?: '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <div class="form-text">Required unless you use the admin key.</div>
                        </div>
                        <?php if (isset($_GET['key'])): ?>
                            <input type="hidden" name="key" value="<?= htmlspecialchars((string)$_GET['key'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Search</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
