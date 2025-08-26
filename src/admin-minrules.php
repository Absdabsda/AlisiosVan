<?php
declare(strict_types=1);
ini_set('display_errors','1'); // ponlo a '0' en prod
error_reporting(E_ALL);

require_once __DIR__ . '/../config/bootstrap_env.php';
require_once __DIR__ . '/../config/db.php';
$pdo = get_pdo();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

function jexit(array $p, int $c=200){ http_response_code($c); echo json_encode($p, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }

/* ===== Auth por cookie ===== */
$adminKeyEnv = env('ADMIN_KEY','');
$cookieKey   = $_COOKIE['admin_key'] ?? '';
if (!$adminKeyEnv || !$cookieKey || !hash_equals($adminKeyEnv, (string)$cookieKey)) {
    jexit(['ok'=>false,'error'=>'forbidden'], 403);
}

/* ===== Input: acepta form y JSON ===== */
$raw   = file_get_contents('php://input') ?: '';
$ctype = $_SERVER['CONTENT_TYPE'] ?? '';
$body  = [];
if ($raw !== '' && stripos($ctype, 'application/json') !== false) {
    $body = json_decode($raw, true) ?: [];
}
$in = array_merge($_GET, $_POST, $body);
$action = (string)($in['action'] ?? '');

try {
    /* === LISTAR REGLAS EN RANGO (para el mini-cal) === */
    if ($action === 'list') {
        $cid = (int)($in['camper_id'] ?? 0);
        if ($cid <= 0) jexit(['ok'=>false, 'error'=>'invalid camper_id'], 400);

        // start (incl.) / end (excl.) vienen del calendar
        $start = isset($in['start']) ? (new DateTime((string)$in['start']))->format('Y-m-d') : null;
        $end   = isset($in['end'])   ? (new DateTime((string)$in['end']))->format('Y-m-d')   : null;

        $sql = "SELECT id, start_date, end_date, min_nights, note
                FROM camper_min_rules
                WHERE camper_id = :cid";
        $p = [':cid'=>$cid];

        if ($start && $end) {
            // overlap: NOT (end < start || start >= end)
            $sql .= " AND NOT (end_date < :s OR start_date >= :e)";
            $p[':s'] = $start;
            $p[':e'] = $end;
        }

        $sql .= " ORDER BY start_date ASC";
        $st = $pdo->prepare($sql);
        $st->execute($p);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $events = array_map(function($r){
            $endEx = (new DateTime($r['end_date']))->modify('+1 day')->format('Y-m-d'); // exclusivo
            return [
                'id'    => 'mr-'.(int)$r['id'],
                'title' => 'Min '.(int)$r['min_nights'],
                'start' => (string)$r['start_date'],
                'end'   => $endEx,
                'allDay'=> true,
                'backgroundColor' => '#FFD166',
                'borderColor'     => '#FFD166',
                'textColor'       => '#000',
                'classNames'      => ['ev-minrule'],
            ];
        }, $rows);

        jexit(['ok'=>true, 'events'=>$events]);
    }

    /* === CREAR/ACTUALIZAR REGLA POR RANGO === */
    if ($action === 'set_range') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') jexit(['ok'=>false,'error'=>'method_not_allowed'],405);

        $cid   = (int)($in['camper_id'] ?? 0);
        $sdS   = trim((string)($in['start_date'] ?? ''));
        $edS   = trim((string)($in['end_date'] ?? ''));
        $min   = (int)($in['min_nights'] ?? 0);
        $note  = trim((string)($in['note'] ?? ''));
        $replace = (int)($in['replace'] ?? 0) === 1;

        if ($cid <= 0 || $sdS === '' || $edS === '') jexit(['ok'=>false,'error'=>'invalid input'], 400);

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

        jexit(['ok'=>true, 'id'=>(int)$pdo->lastInsertId()]);
    }

    /* === BORRAR REGLA === */
    if ($action === 'delete') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') jexit(['ok'=>false,'error'=>'method_not_allowed'],405);
        $id = (int)($in['id'] ?? 0);
        if ($id <= 0) jexit(['ok'=>false, 'error'=>'invalid id'], 400);
        $st = $pdo->prepare("DELETE FROM camper_min_rules WHERE id=? LIMIT 1");
        $st->execute([$id]);
        jexit(['ok'=>true, 'deleted'=>(int)$st->rowCount()]);
    }

    jexit(['ok'=>false, 'error'=>'unknown action'], 400);
} catch (Throwable $e) {
    jexit(['ok'=>false, 'error'=>$e->getMessage()], 400);
}
