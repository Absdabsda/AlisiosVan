<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require_once __DIR__ . '/../config/bootstrap_env.php';
require __DIR__ . '/../config/db.php';
$pdo = get_pdo();

header('Content-Type: application/json; charset=utf-8');

$key = $_POST['key'] ?? $_GET['key'] ?? '';
$adminKey = env('ADMIN_KEY','');
if (!$key || !hash_equals($adminKey, (string)$key)) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'forbidden']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    // Opcional: fuerza POST para acciones que cambian estado
    if (in_array($action, ['create','delete'], true) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Method not allowed');
    }

    if ($action === 'create') {
        $camperId = (int)($_POST['camper_id'] ?? 0);
        $start    = trim((string)($_POST['start_date'] ?? ''));
        $end      = trim((string)($_POST['end_date'] ?? ''));
        $reason   = trim((string)($_POST['reason'] ?? ''));

        if ($camperId<=0 || !$start || !$end) {
            throw new RuntimeException('Missing fields');
        }

        // Normaliza fechas (YYYY-MM-DD)
        $sd = (new DateTime($start))->format('Y-m-d');
        $ed = (new DateTime($end))->format('Y-m-d');

        // Validación simple de rango
        if ($ed < $sd) {
            throw new RuntimeException('end_date must be >= start_date');
        }

        // Inserta en blackout_dates
        $st = $pdo->prepare("
            INSERT INTO blackout_dates (camper_id, start_date, end_date, reason)
            VALUES (?,?,?,?)
        ");
        $st->execute([$camperId, $sd, $ed, $reason]);

        echo json_encode(['ok'=>true, 'id'=>$pdo->lastInsertId()]);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id<=0) throw new RuntimeException('Invalid id');

        $st = $pdo->prepare("DELETE FROM blackout_dates WHERE id=?");
        $st->execute([$id]);

        echo json_encode(['ok'=>true, 'deleted'=>$st->rowCount()]);
        exit;
    }

    throw new RuntimeException('Unknown action');
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
    exit;
}
