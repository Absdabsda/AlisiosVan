<?php
declare(strict_types=1);
ini_set('display_errors','1'); // ponlo a '0' cuando acabe la prueba
error_reporting(E_ALL);

require_once __DIR__ . '/../config/bootstrap_env.php';
require __DIR__ . '/../config/db.php';
$pdo = get_pdo();

header('Content-Type: application/json; charset=utf-8');
function jexit(array $p, int $c=200){ http_response_code($c); echo json_encode($p, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }

$key = $_GET['key'] ?? $_POST['key'] ?? '';
if (!$key || !hash_equals(env('ADMIN_KEY',''), (string)$key)) jexit(['ok'=>false, 'error'=>'forbidden'], 403);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    /* === LISTAR REGLAS EN RANGO (para el mini-cal) === */
    if ($action === 'list') {
        $cid = (int)($_GET['camper_id'] ?? 0);
        if ($cid <= 0) jexit(['ok'=>false, 'error'=>'invalid camper_id'], 400);

        $start = $_GET['start'] ?? null;   // YYYY-MM-DD (inclusive)
        $end   = $_GET['end']   ?? null;   // YYYY-MM-DD (exclusivo, viene de FullCalendar)

        $sql = "SELECT id, start_date, end_date, min_nights, note
            FROM camper_min_rules
            WHERE camper_id = :cid";
        $p = [':cid' => $cid];

        if ($start && $end) {
            $sql .= " AND NOT (end_date < :s OR start_date >= :e)";
            $p[':s'] = (new DateTime($start))->format('Y-m-d');
            $p[':e'] = (new DateTime($end))->format('Y-m-d');
        }

        $sql .= " ORDER BY start_date ASC";
        $st = $pdo->prepare($sql); $st->execute($p);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $events = array_map(function($r){
            $endEx = (new DateTime($r['end_date']))->modify('+1 day')->format('Y-m-d'); // exclusivo
            return [
                'id'    => 'mr-'.$r['id'],
                'title' => 'Min '.(int)$r['min_nights'],
                'start' => (string)$r['start_date'],
                'end'   => $endEx,
                'allDay'=> true,
                'backgroundColor' => '#FFD166',
                'borderColor'     => '#FFD166',
                'textColor'       => '#000',
                'classNames'      => ['ev-minrule']
            ];
        }, $rows);

        jexit(['ok'=>true, 'events'=>$events]);
    }

    /* === CREAR/ACTUALIZAR REGLA POR RANGO === */
    if ($action === 'set_range' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $cid   = (int)($_POST['camper_id'] ?? 0);
        $sdS   = trim((string)($_POST['start_date'] ?? ''));
        $edS   = trim((string)($_POST['end_date'] ?? ''));
        $min   = (int)($_POST['min_nights'] ?? 0);
        $note  = trim((string)($_POST['note'] ?? ''));
        $replace = (int)($_POST['replace'] ?? 0) === 1;

        if ($cid <= 0 || !$sdS || !$edS) jexit(['ok'=>false, 'error'=>'invalid input'], 400);

        $sd = (new DateTime($sdS))->format('Y-m-d');
        $ed = (new DateTime($edS))->format('Y-m-d');
        if ($ed < $sd) jexit(['ok'=>false, 'error'=>'end_date < start_date'], 400);
        if ($min < 1 || $min > 60) jexit(['ok'=>false, 'error'=>'invalid min_nights'], 400);

        if ($replace) {
            $del = $pdo->prepare("DELETE FROM camper_min_rules WHERE camper_id=? AND NOT (end_date < ? OR start_date > ?)");
            $del->execute([$cid, $sd, $ed]);
        }

        $st = $pdo->prepare("INSERT INTO camper_min_rules (camper_id, start_date, end_date, min_nights, note) VALUES (?,?,?,?,?)");
        $st->execute([$cid, $sd, $ed, $min, $note]);

        jexit(['ok'=>true, 'id'=>$pdo->lastInsertId()]);
    }

    /* === BORRAR REGLA === */
    if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) jexit(['ok'=>false, 'error'=>'invalid id'], 400);
        $st = $pdo->prepare("DELETE FROM camper_min_rules WHERE id=?");
        $st->execute([$id]);
        jexit(['ok'=>true, 'deleted'=>$st->rowCount()]);
    }

    jexit(['ok'=>false, 'error'=>'unknown action'], 400);
} catch (Throwable $e) {
    jexit(['ok'=>false, 'error'=>$e->getMessage()], 400);
}
