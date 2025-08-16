<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type: application/json');

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

require __DIR__ . '/../config/db.php';
$pdo = get_pdo();

$start    = $_GET['start'] ?? null;      // Y-m-d
$end      = $_GET['end']   ?? null;      // Y-m-d
$series   = trim($_GET['series'] ?? ''); // T3/T4 (opcional)
$maxPrice = isset($_GET['maxPrice']) && $_GET['maxPrice'] !== ''
    ? (float)$_GET['maxPrice']
    : null;

try {
    if (!$start || !$end) {
        throw new Exception('Faltan start y end (Y-m-d).');
    }

    $d1 = DateTime::createFromFormat('Y-m-d', $start);
    $d2 = DateTime::createFromFormat('Y-m-d', $end);
    if (!$d1 || !$d2) throw new Exception('Formato de fecha inválido (usa Y-m-d).');
    if ($d1 >= $d2) throw new Exception('end debe ser posterior a start.');

    // Construimos el WHERE opcional
    $where = [];
    if ($series !== '') {
        $where[] = 'c.series = :series';
    }
    if ($maxPrice !== null && $maxPrice > 0) {
        $where[] = 'c.price_per_night <= :maxPrice';
    }
    $whereSql = $where ? ' AND ' . implode(' AND ', $where) : '';

    // IMPORTANTE: c.image existe (lo añadiste) y price_per_night es el nombre real
    $sql = "
        SELECT c.id, c.name, c.series, c.price_per_night, c.image
        FROM campers c
        WHERE 1=1 $whereSql
          AND NOT EXISTS (
            SELECT 1
            FROM reservations r
            WHERE r.camper_id = c.id
              AND r.start_date < :end
              AND r.end_date   > :start
              AND (
                    r.status = 'paid'
                 OR (r.status = 'pending' AND r.created_at > NOW() - INTERVAL 30 MINUTE)
              )
          )
        ORDER BY c.price_per_night ASC, c.name ASC
    ";

    $st = $pdo->prepare($sql);

    // Bind obligatorios
    $st->bindValue(':start', $start);
    $st->bindValue(':end',   $end);

    // Bind opcionales SOLO si están en el SQL
    if ($series !== '') {
        $st->bindValue(':series', $series);
    }
    if ($maxPrice !== null && $maxPrice > 0) {
        $st->bindValue(':maxPrice', $maxPrice);
    }

    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'count' => count($rows), 'campers' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
