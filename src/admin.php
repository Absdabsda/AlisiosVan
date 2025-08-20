<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require_once __DIR__ . '/../config/bootstrap_env.php';
require __DIR__.'/../config/db.php';
$pdo = get_pdo();

// Auth por clave de admin (query ?key=...)
$key      = $_GET['key'] ?? '';
$adminKey = env('ADMIN_KEY','');
if (!$key || !hash_equals($adminKey, $key)) { http_response_code(403); echo "Forbidden"; exit; }

$status  = $_GET['status'] ?? 'all';
$allowed = ['all','pending','paid','cancelled_by_customer'];
if(!in_array($status,$allowed,true)) $status='all';

$sql = "
  SELECT r.id, r.start_date, r.end_date, r.status, r.created_at, r.paid_at,
         r.stripe_session_id, r.manage_token,
         c.name AS camper, c.price_per_night,
         DATEDIFF(r.end_date, r.start_date) AS nights
  FROM reservations r
  JOIN campers c ON c.id = r.camper_id
";
$params=[];
if($status!=='all'){ $sql .= " WHERE r.status = ?"; $params[]=$status; }
$sql .= " ORDER BY r.created_at DESC LIMIT 300";
$st=$pdo->prepare($sql); $st->execute($params);
$rows=$st->fetchAll(PDO::FETCH_ASSOC);

function baseUrl(): string {
    $env = rtrim(env('PUBLIC_BASE_URL',''), '/');
    if ($env) return $env;
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return rtrim("$scheme://$host$dir", '/');
}
$feed = baseUrl()."/company-ical.php?key=".urlencode(env('COMPANY_ICAL_KEY',''));
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin · Reservas | Alisios Van</title>

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

    <script src="js/header.js" defer></script>
    <!-- FullCalendar (CSS + JS global) -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/main.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js" defer></script>

    <style>
        /* Integra con tu estética */
        #adminCalendar { background:#fff; border-radius:12px; box-shadow: var(--box-shadow-medium); padding: 12px; }
        .fc .fc-button-primary {
            background: var(--color-mar); border-color: var(--color-mar);
        }
        .fc .fc-button-primary:hover {
            background: var(--color-mar-oscuro); border-color: var(--color-mar-oscuro);
        }
        /* Canceladas: tachadas y algo más pálidas */
        .fc-event.ev-cancelled { opacity:.75; text-decoration: line-through; }
    </style>

</head>
<body>
<?php include 'inc/header.inc'; ?>

<section class="page-hero" style="background-image:url('img/landing-matcha.02.31.jpeg')">
    <div class="page-hero__content">
        <h1 class="page-hero__title">Admin · Reservas</h1>
        <p class="mt-2">Resumen de reservas y acceso a calendario</p>
    </div>
</section>

<main class="wrap">
    <?php
    $eventsUrl = baseUrl() . '/admin-events.php?key=' . urlencode(env('ADMIN_KEY',''));
    ?>

    <div class="cardy mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h5 mb-0">Calendario</h2>
            <button id="btnRefreshCal" type="button" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-clockwise"></i> Actualizar
            </button>
        </div>
        <div id="adminCalendar"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('btnRefreshCal');
            const calendarEl = document.getElementById('adminCalendar');
            if (!calendarEl || typeof FullCalendar === 'undefined') return;

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                height: 'auto',
                firstDay: 1,
                headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listMonth' },
                events: {
                    url: '<?= htmlspecialchars($eventsUrl, ENT_QUOTES, "UTF-8") ?>',
                    // cache-bust en cada petición
                    extraParams: () => ({ _ts: Date.now() }),
                    failure: () => alert('No se pudieron cargar los eventos.')
                },
                eventClick: function(info) {
                    if (info.event.url) {
                        info.jsEvent.preventDefault();
                        window.open(info.event.url, '_blank', 'noopener');
                    }
                },
                eventDisplay: 'block'
            });

            // feedback de carga
            calendar.on('loading', function(isLoading){
                if (!btn) return;
                btn.disabled = isLoading;
                btn.classList.toggle('disabled', isLoading);
            });

            calendar.render();

            btn?.addEventListener('click', () => {
                calendar.refetchEvents();   // ahora siempre pega al server (por _ts)
            });
        });
    </script>


    <div class="cardy mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div class="mb-2">
                <a class="btn btn-outline-secondary btn-sm" href="?key=<?= urlencode($key) ?>&status=all">Todas</a>
                <a class="btn btn-outline-secondary btn-sm" href="?key=<?= urlencode($key) ?>&status=pending">Pendientes</a>
                <a class="btn btn-outline-secondary btn-sm" href="?key=<?= urlencode($key) ?>&status=paid">Pagadas</a>
                <a class="btn btn-outline-secondary btn-sm" href="?key=<?= urlencode($key) ?>&status=cancelled_by_customer">Canceladas</a>
            </div>
            <div>
                <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($feed) ?>" target="_blank" rel="noopener">
                    <i class="bi bi-calendar2-week"></i> Suscribirse al calendario (iCal)
                </a>
            </div>
        </div>

        <div class="table-responsive mt-3">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>#</th><th>Camper</th><th>Fechas</th><th>Noches</th><th>Precio/noche</th><th>Status</th><th>Creada</th><th>Pagada</th><th>Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($rows as $r): ?>
                    <tr>
                        <td><?= (int)$r['id'] ?></td>
                        <td><?= htmlspecialchars($r['camper']) ?></td>
                        <td><?= htmlspecialchars($r['start_date']) ?> → <?= htmlspecialchars($r['end_date']) ?></td>
                        <td><?= (int)$r['nights'] ?></td>
                        <td>€<?= number_format((float)$r['price_per_night'],2) ?></td>
                        <td><?= htmlspecialchars($r['status']) ?></td>
                        <td class="text-muted small"><?= htmlspecialchars((string)$r['created_at']) ?></td>
                        <td class="text-muted small"><?= htmlspecialchars((string)$r['paid_at']) ?></td>
                        <td>
                            <?php if(!empty($r['manage_token'])): ?>
                                <a class="btn btn-outline-secondary btn-sm" target="_blank"
                                   href="manage.php?rid=<?= (int)$r['id'] ?>&t=<?= urlencode($r['manage_token']) ?>&by=admin">
                                   Abrir gestión
                                </a>

                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include 'inc/footer.inc'; ?>
</body>
</html>
