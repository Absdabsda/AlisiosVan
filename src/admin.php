<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require_once __DIR__ . '/../config/bootstrap_env.php';
require __DIR__ . '/../config/db.php';
$pdo = get_pdo();

/* ===== Auth ===== */
$key = $_GET['key'] ?? '';
$adminKey = env('ADMIN_KEY','');
if (!$key || !hash_equals($adminKey, $key)) { http_response_code(403); echo "Forbidden"; exit; }
setcookie('admin_key', (string)$key, [
    'expires'=> time()+60*60*24*30, 'path'=>'/', 'secure'=> (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off'),
    'httponly'=>true,'samesite'=>'Lax'
]);

/* ===== Filtros reservas ===== */
$status  = $_GET['status'] ?? 'all';
$allowed = ['all','pending','paid','cancelled_by_customer','cancelled_by_admin','cancelled'];
if(!in_array($status,$allowed,true)) $status='all';

/* ===== Carga reservas ===== */
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

/* ===== Utilidades ===== */
function baseUrl(): string {
    $env = rtrim(env('PUBLIC_BASE_URL',''), '/');
    if ($env) return $env;
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return rtrim("$scheme://$host$dir", '/');
}
$feed      = baseUrl()."/company-ical.php?key=".urlencode(env('COMPANY_ICAL_KEY',''));
$eventsUrl = baseUrl() . '/admin-events.php?key=' . urlencode($adminKey);

/* ===== Campers para selects/tab ===== */
$campers = $pdo->query("SELECT id, name FROM campers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$campersAdmin = $pdo->query("SELECT id, name, price_per_night, COALESCE(min_nights,2) AS min_nights FROM campers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
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
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/admin.css">

    <script>
        window.ADMIN_KEY = '<?= htmlspecialchars($adminKey, ENT_QUOTES, "UTF-8") ?>';
        window.ADMIN_EVENTS_URL = '<?= htmlspecialchars($eventsUrl, ENT_QUOTES, "UTF-8") ?>';
        window.ADMIN_MINRULES_URL = '<?= htmlspecialchars(baseUrl()."/admin-minrules.php?key=".urlencode($adminKey), ENT_QUOTES, "UTF-8") ?>';
    </script>
</head>
<body>
<?php include 'inc/header.inc'; ?>

<section class="page-hero" style="background-image:url('img/landing-matcha.02.31.jpeg')">
    <div class="page-hero__content">
        <h1 class="page-hero__title">Admin · Reservas</h1>
        <p class="mt-2">Resumen de reservas, calendario y gestión de campers</p>
    </div>
</section>

<main class="wrap">
    <!-- Tabs -->
    <ul class="nav nav-pills mb-3" id="adminTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-cal" type="button"><i class="bi bi-calendar2-week me-1"></i> Calendario / Bloqueos</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-res" type="button"><i class="bi bi-list-check me-1"></i> Reservas</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-campers" type="button"><i class="bi bi-truck-front me-1"></i> Campers</button></li>
    </ul>

    <div class="tab-content">
        <!-- Calendario -->
        <div class="tab-pane fade show active" id="tab-cal">
            <div class="cardy mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h5 mb-0">Calendario</h2>
                    <div class="d-flex gap-2 align-items-center">
                        <select id="blockCamper" class="form-select form-select-sm" style="min-width:240px">
                            <option value="">— Elegir camper para bloquear —</option>
                            <?php foreach($campers as $c): ?>
                                <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button id="btnRefreshCal" type="button" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-clockwise"></i> Actualizar
                        </button>
                    </div>
                </div>

                <div class="legend mb-2 text-muted">
                    <span><i style="background:#80C1D0"></i> Reservas (pagadas)</span>
                    <span><i style="background:#CBB49E"></i> Pendientes</span>
                    <span><i style="background:#EF476F"></i> Canceladas</span>
                    <span><i style="background:#60666d"></i> Bloqueos</span>
                </div>

                <div id="adminCalendar"></div>
            </div>
        </div>

        <!-- Reservas -->
        <div class="tab-pane fade" id="tab-res">
            <div class="cardy mb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <div class="mb-2">
                        <a class="btn btn-outline-secondary btn-sm" href="?key=<?= urlencode($key) ?>&status=all#tab-res">Todas</a>
                        <a class="btn btn-outline-secondary btn-sm" href="?key=<?= urlencode($key) ?>&status=pending#tab-res">Pendientes</a>
                        <a class="btn btn-outline-secondary btn-sm" href="?key=<?= urlencode($key) ?>&status=paid#tab-res">Pagadas</a>
                        <a class="btn btn-outline-secondary btn-sm" href="?key=<?= urlencode($key) ?>&status=cancelled_by_customer#tab-res">Canceladas (cliente)</a>
                        <a class="btn btn-outline-secondary btn-sm" href="?key=<?= urlencode($key) ?>&status=cancelled_by_admin#tab-res">Canceladas (admin)</a>
                    </div>
                    <div>
                        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($feed) ?>" target="_blank" rel="noopener">
                            <i class="bi bi-calendar2-week"></i> Suscribirse al calendario (iCal)
                        </a>
                    </div>
                </div>

                <div class="table-responsive mt-3">
                    <table class="table align-middle">
                        <thead><tr><th>#</th><th>Camper</th><th>Fechas</th><th>Noches</th><th>Precio/noche</th><th>Status</th><th>Creada</th><th>Pagada</th><th>Acciones</th></tr></thead>
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
                                        <a class="btn btn-outline-secondary btn-sm" target="_blank" href="manage.php?rid=<?= (int)$r['id'] ?>&t=<?= urlencode($r['manage_token']) ?>">Abrir gestión</a>
                                    <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Campers -->
        <div class="tab-pane fade" id="tab-campers">
            <div class="cardy">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h5 mb-0">Campers · Precios y mínimo de noches</h2>
                    <small class="text-muted">Define mínimo base y reglas por fechas</small>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>Camper</th>
                            <th style="width:180px">Precio/noche (€)</th>
                            <th style="width:180px">Mín. noches (base)</th>
                            <th style="width:260px">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($campersAdmin as $c): ?>
                            <tr data-id="<?= (int)$c['id'] ?>">
                                <td class="fw-medium"><?= htmlspecialchars($c['name']) ?></td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">€</span>
                                        <input type="number" class="form-control text-end" step="0.01" min="0" name="price_per_night" value="<?= htmlspecialchars(number_format((float)$c['price_per_night'], 2, '.', '')) ?>">
                                    </div>
                                </td>
                                <td><input type="number" class="form-control form-control-sm text-end" min="1" max="60" name="min_nights" value="<?= (int)$c['min_nights'] ?>"></td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-primary btn-sm btnSaveCamper"><i class="bi bi-save"></i> Guardar</button>
                                        <button class="btn btn-outline-secondary btn-sm btnOpenRules" data-camper="<?= (int)$c['id'] ?>" data-bs-toggle="collapse" data-bs-target="#rules-<?= (int)$c['id'] ?>"><i class="bi bi-calendar-range"></i> Reglas por fechas</button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="collapse bg-light" id="rules-<?= (int)$c['id'] ?>">
                                <td colspan="4">
                                    <div class="p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong>Reglas de mínimo de noches</strong>
                                            <button type="button" class="btn btn-sm btn-outline-secondary btnApplyMonth">Aplicar al <span class="text-decoration-underline">mes visible</span></button>
                                        </div>
                                        <div class="mini-cal"></div>
                                        <div class="form-text mt-2">Arrastra para seleccionar un <b>rango</b> y fijar el mínimo. Clic en un bloque “Min X” para eliminarlo.</div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal bloqueos -->
    <div class="modal fade" id="blkModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Crear bloqueo</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Camper</label>
                        <select id="blkCamper" class="form-select form-select-sm">
                            <option value="">— Elegir camper —</option>
                            <?php foreach($campers as $c): ?><option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col"><label class="form-label">Desde</label><input type="date" id="blkStart" class="form-control"></div>
                        <div class="col"><label class="form-label">Hasta <small class="text-muted">(incl.)</small></label><input type="date" id="blkEnd" class="form-control"></div>
                    </div>
                    <div class="mt-2"><label class="form-label">Motivo (opcional)</label><input type="text" id="blkReason" class="form-control" maxlength="120" placeholder="Mantenimiento, ITV, etc."></div>
                    <div class="form-text mt-2">El día “Hasta” es inclusivo.</div>
                </div>
                <div class="modal-footer"><button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button id="blkCreate" class="btn btn-primary">Crear bloqueo</button></div>
            </div></div>
    </div>

    <!-- Modal reglas por fechas -->
    <div class="modal fade" id="minRuleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Fijar mínimo de noches (rango)</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
                <div class="modal-body">
                    <input type="hidden" id="minRuleCamper">
                    <div class="row g-2">
                        <div class="col"><label class="form-label">Desde</label><input type="date" id="minRuleStart" class="form-control"></div>
                        <div class="col"><label class="form-label">Hasta <small class="text-muted">(incl.)</small></label><input type="date" id="minRuleEnd" class="form-control"></div>
                    </div>
                    <div class="mt-2"><label class="form-label">Mín. noches</label><input type="number" id="minRuleValue" class="form-control" min="1" max="60"></div>
                    <div class="mt-2"><label class="form-label">Nota (opcional)</label><input type="text" id="minRuleNote" class="form-control" maxlength="120"></div>
                    <div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="minRuleReplace"><label class="form-check-label" for="minRuleReplace">Sustituir reglas existentes que se solapen</label></div>
                </div>
                <div class="modal-footer"><button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button id="minRuleSave" class="btn btn-primary">Guardar regla</button></div>
            </div></div>
    </div>
</main>

<?php include 'inc/footer.inc'; ?>

<!-- JS al final -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="js/header.js"></script>
<script src="js/admin-blocks.js"></script>
<script src="js/admin-minrules.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tabs = document.querySelectorAll('#adminTabs [data-bs-toggle="tab"]');
        if (location.hash) {
            const btn = document.querySelector(`[data-bs-target="${location.hash}"]`);
            if (btn) new bootstrap.Tab(btn).show();
        }
        tabs.forEach(btn => btn.addEventListener('shown.bs.tab', e => {
            history.replaceState(null, '', (e.target.dataset.bsTarget || '').toString());
        }));
    });
</script>
</body>
</html>
