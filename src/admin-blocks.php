<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require_once __DIR__ . '/../config/bootstrap_env.php';
require_once __DIR__ . '/../config/db.php';
$pdo = get_pdo();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

/* ===== Auth por cookie ===== */
$adminKeyEnv = env('ADMIN_KEY','');
$cookieKey   = $_COOKIE['admin_key'] ?? '';
if (!$adminKeyEnv || !$cookieKey || !hash_equals($adminKeyEnv, (string)$cookieKey)) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'forbidden'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ===== Input: acepta form y JSON ===== */
$raw   = file_get_contents('php://input') ?: '';
$ctype = $_SERVER['CONTENT_TYPE'] ?? '';
$body  = [];
if (stripos($ctype, 'application/json') !== false && $raw !== '') {
    $body = json_decode($raw, true) ?: [];
}
$in = array_merge($_GET, $_POST, $body);

$action = (string)($in['action'] ?? '');

/* Fuerza POST para acciones que cambian estado */
if (in_array($action, ['create','delete'], true) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'error'=>'method_not_allowed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    if ($action === 'create') {
        $camperId = (int)($in['camper_id'] ?? 0);
        $start    = trim((string)($in['start_date'] ?? ''));
        $end      = trim((string)($in['end_date'] ?? ''));
        $reason   = trim((string)($in['reason'] ?? ''));

        if ($camperId <= 0 || $start === '' || $end === '') {
            throw new RuntimeException('missing_fields');
        }

        // Normaliza YYYY-MM-DD y valida rango
        $sd = (new DateTime($start))->format('Y-m-d');
        $ed = (new DateTime($end))->format('Y-m-d');
        if ($ed < $sd) throw new RuntimeException('end_before_start');

        $st = $pdo->prepare("INSERT INTO blackout_dates (camper_id, start_date, end_date, reason) VALUES (?,?,?,?)");
        $st->execute([$camperId, $sd, $ed, $reason]);

        echo json_encode(['ok'=>true,'id'=>(int)$pdo->lastInsertId()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($in['id'] ?? 0);
        if ($id <= 0) throw new RuntimeException('invalid_id');

        $st = $pdo->prepare("DELETE FROM blackout_dates WHERE id=? LIMIT 1");
        $st->execute([$id]);

        echo json_encode(['ok'=>true,'deleted'=>(int)$st->rowCount()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // opcional: listado para depurar/usar en UI
    if ($action === 'list') {
        $st = $pdo->query("SELECT id, camper_id, start_date, end_date, reason FROM blackout_dates ORDER BY start_date DESC LIMIT 500");
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['ok'=>true,'rows'=>$rows], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    throw new RuntimeException('unknown_action');
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
