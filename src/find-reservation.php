<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require_once __DIR__ . '/../config/bootstrap_env.php';
require_once __DIR__ . '/../config/db.php';
$pdo = get_pdo();

/* ===== i18n fallback: evita fatal si __() aún no está definida ===== */
if (!function_exists('__')) {
    function __(string $s): string { return $s; }
}

/* ===================== helpers ===================== */
function norm($s){ return mb_strtolower(trim((string)$s)); }

/** Devuelve la admin key válida (por ?key o cookie) y deja cookie; null si no hay */
function admin_key(): ?string {
    $envKey = env('ADMIN_KEY','');
    if (!$envKey) return null;

    // prioriza ?key
    $k = $_GET['key'] ?? null;
    if (is_string($k) && $k !== '' && hash_equals($envKey, $k)) {
        if (empty($_COOKIE['admin_key'])) {
            setcookie('admin_key', $k, [
                'expires'  => time() + 60*60*24*30,
                'path'     => '/',
                'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        return (string)$k;
    }

    // cookie existente
    $ck = $_COOKIE['admin_key'] ?? '';
    if ($ck && hash_equals($envKey, (string)$ck)) return (string)$ck;

    return null;
}

function columnExists(PDO $pdo, string $table, string $column): bool {
    $sql = "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([':t'=>$table, ':c'=>$column]);
    return (bool)$st->fetchColumn();
}


/* ===================== entrada ===================== */
$rid        = (int)($_GET['rid']   ?? $_POST['rid']   ?? 0);
$emailInput = (string)($_GET['email'] ?? $_POST['email'] ?? '');
$email      = norm($emailInput);

// flags para decidir modo
$forcePublic = isset($_GET['public']) && $_GET['public'] !== '0';
// si hay email y NO han pedido admin explícito, asumimos público
if ($email !== '' && !isset($_GET['admin'])) $forcePublic = true;

$adminKey = admin_key();
$isAdmin  = !$forcePublic && ($adminKey !== null);

/* ===================== búsqueda/redirección ===================== */
try {
    if ($rid > 0) {
        if ($isAdmin) {
            // Admin: basta con que exista la reserva
            $st = $pdo->prepare("SELECT id FROM reservations WHERE id=? LIMIT 1");
            $st->execute([$rid]);
            if ($st->fetchColumn()) {
                header('Location: manage-admin.php?rid='.(int)$rid);
                exit;
            }
        } else {
            // Público: requiere email (case-insensitive) contra reservations
            // Público: requiere email (case-insensitive)
            if ($email !== '') {
                $row = null;

                // ¿Podemos unir con customers?
                $canJoinCustomers = columnExists($pdo, 'reservations', 'customer_id')
                    && columnExists($pdo, 'customers', 'id')
                    && columnExists($pdo, 'customers', 'email');

                $conds  = [];
                $params = [$rid];
                $join   = $canJoinCustomers ? "LEFT JOIN customers cu ON cu.id = r.customer_id" : "";

                // candidates en reservations.*
                if (columnExists($pdo, 'reservations', 'customer_email')) {
                    $conds[]  = "(r.customer_email IS NOT NULL AND r.customer_email <> '' AND LOWER(TRIM(r.customer_email)) = LOWER(?))";
                    $params[] = $email;
                }
                if (columnExists($pdo, 'reservations', 'email')) {
                    $conds[]  = "(r.email IS NOT NULL AND r.email <> '' AND LOWER(TRIM(r.email)) = LOWER(?))";
                    $params[] = $email;
                }

                // candidate en customers.email
                if ($canJoinCustomers) {
                    $conds[]  = "(cu.email IS NOT NULL AND cu.email <> '' AND LOWER(TRIM(cu.email)) = LOWER(?))";
                    $params[] = $email;
                }

                if ($conds) {
                    $sql = "
                        SELECT r.id, r.manage_token
                        FROM reservations r
                        $join
                        WHERE r.id = ?
                          AND (".implode(' OR ', $conds).")
                        LIMIT 1
                    ";
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
                    $qs = http_build_query(['rid'=>$rid,'t'=>$row['manage_token']]);
                    header('Location: manage.php?'.$qs);
                    exit;
                }
            }

        }
    }
} catch (Throwable $e) {
    // deja caer a la vista "no encontrado"
}

/* ===================== vista: no encontrado ===================== */
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __('Find Reservation') ?></title>

    <!-- evita traducción automática de Chrome -->
    <meta name="google" content="notranslate">

    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flag-icons/css/flag-icons.min.css">

    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/manage.css">
    <link rel="stylesheet" href="css/cookies.css">

    <script src="js/header.js" defer></script>
    <script src="js/cookies.js" defer></script>

    <style>
        :root { --header-bg-rgb: 84,70,62; } /* #54463E */
    </style>
</head>
<body>
<?php include 'inc/header.inc'; ?>

<section class="page-hero manage-hero">
    <div class="page-hero__content">
        <h1 class="page-hero__title"><?= __('Find your reservation') ?></h1>
    </div>
</section>

<main class="container py-4" style="max-width:720px;">
    <div class="alert alert-warning">
        <?= __('We couldn’t find a reservation with that combination. Please check your Reservation # and email.') ?>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#manageBookingModal">
        <?= __('Try again') ?>
    </button>
</main>

<!-- Modal buscar -->
<div class="modal fade" id="manageBookingModal" tabindex="-1" aria-labelledby="manageBookingLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="get" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="manageBookingLabel"><?= __('Find your reservation') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= __('Close') ?>"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?= __('Reservation #') ?></label>
                        <input type="number" class="form-control" name="rid" value="<?= $rid ?: '' ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= __('Email') ?></label>
                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($emailInput ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <div class="form-text"><?= __('Required unless you use the admin key.') ?></div>
                    </div>
                    <!-- Fuerza modo público en siguientes envíos -->
                    <input type="hidden" name="public" value="1">
                    <?php if (isset($_GET['key'])): ?>
                        <input type="hidden" name="key" value="<?= htmlspecialchars((string)$_GET['key'], ENT_QUOTES, 'UTF-8') ?>">
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= __('Close') ?></button>
                    <button type="submit" class="btn btn-primary"><?= __('Search') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'inc/footer.inc'; ?>
</body>
</html>
