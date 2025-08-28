<?php
declare(strict_types=1);

ini_set('display_errors','1'); // pon a '0' en prod
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../config/bootstrap_env.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/inc/pricing.php'; // ← precios por reglas
$pdo = get_pdo();

$start    = $_GET['start'] ?? null; // YYYY-MM-DD (incl.)
$end      = $_GET['end']   ?? null; // YYYY-MM-DD (excl.)
$series   = trim((string)($_GET['series']   ?? ''));
$maxPrice = isset($_GET['maxPrice']) && $_GET['maxPrice'] !== '' ? (float)$_GET['maxPrice'] : null;

try {
    if (!$start || !$end) throw new RuntimeException('Faltan start y end.');
    $d1 = DateTime::createFromFormat('Y-m-d', $start);
    $d2 = DateTime::createFromFormat('Y-m-d', $end);
    if (!$d1 || !$d2) throw new RuntimeException('Formato de fecha inválido.');
    if ($d1 >= $d2)   throw new RuntimeException('end debe ser posterior a start.');
    $nights = (int)$d1->diff($d2)->days;

    // Filtros opcionales
    $where = [];
    if ($series !== '')                      $where[] = 'c.series = :series';
    if ($maxPrice !== null && $maxPrice > 0) $where[] = 'c.price_per_night <= :maxPrice';
    $whereSql = $where ? ' AND ' . implode(' AND ', $where) : '';

    // Requisito de mínimo de noches (base y reglas)
    $requiredExpr = "
      GREATEST(
        COALESCE(c.min_nights, 2),
        COALESCE((
          SELECT MAX(mr.min_nights)
          FROM camper_min_rules mr
          WHERE mr.camper_id = c.id
            AND mr.start_date < :end_rules
            AND DATE_ADD(mr.end_date, INTERVAL 1 DAY) > :start_rules
        ), 0)
      )
    ";

    // Consulta principal (aplica mínimo + ocupa/bloquea)
    $sql = "
      SELECT c.id, c.name, c.series, c.price_per_night, c.image
      FROM campers c
      WHERE 1=1
        $whereSql
        AND :nights >= ($requiredExpr)

        AND NOT EXISTS (
          SELECT 1 FROM reservations r
          WHERE r.camper_id = c.id
            AND r.start_date < :end_res
            AND r.end_date   > :start_res
            AND ( r.status='paid'
               OR (r.status='pending' AND r.created_at > NOW() - INTERVAL 30 MINUTE))
        )
        AND NOT EXISTS (
          SELECT 1 FROM blackout_dates b
          WHERE b.camper_id = c.id
            AND b.start_date < :end_blk
            AND DATE_ADD(b.end_date, INTERVAL 1 DAY) > :start_blk
        )
      ORDER BY c.price_per_night ASC, c.name ASC
    ";
    $st = $pdo->prepare($sql);

    // Binds comunes
    $st->bindValue(':nights', $nights, PDO::PARAM_INT);
    $st->bindValue(':start_rules', $start);
    $st->bindValue(':end_rules',   $end);
    $st->bindValue(':start_res',   $start);
    $st->bindValue(':end_res',     $end);
    $st->bindValue(':start_blk',   $start);
    $st->bindValue(':end_blk',     $end);

    // Filtros opcionales
    if ($series !== '')                      $st->bindValue(':series',   $series);
    if ($maxPrice !== null && $maxPrice > 0) $st->bindValue(':maxPrice', $maxPrice);

    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    // Inyectar precios calculados por reglas
    $campers = [];
    foreach ($rows as $r) {
        $cid = (int)$r['id'];
        $r['price_label'] = nightly_price($pdo, $cid, $start);             // primera noche efectiva
        $r['total_price'] = sum_price_range($pdo, $cid, $start, $end);     // total del rango (opcional)
        $campers[] = $r;
    }

    // (Opcional) ordenar por precio efectivo y luego nombre
    if ($campers) {
        usort($campers, fn($a,$b) =>
        ($a['price_label'] <=> $b['price_label']) ?: strcmp((string)$a['name'], (string)$b['name'])
        );

        echo json_encode([
            'ok'      => true,
            'count'   => count($campers),
            'campers' => $campers,
            'meta'    => ['nights' => $nights]
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        exit;
    }

    // -------- Sin resultados: ¿mínimo de noches o ocupado? --------
    $sql2 = "
      SELECT
        c.id, c.name,
        ($requiredExpr) AS required_min
      FROM campers c
      WHERE 1=1
        $whereSql
        AND NOT EXISTS (
          SELECT 1 FROM reservations r
          WHERE r.camper_id = c.id
            AND r.start_date < :end_res
            AND r.end_date   > :start_res
            AND ( r.status='paid'
               OR (r.status='pending' AND r.created_at > NOW() - INTERVAL 30 MINUTE))
        )
        AND NOT EXISTS (
          SELECT 1 FROM blackout_dates b
          WHERE b.camper_id = c.id
            AND b.start_date < :end_blk
            AND DATE_ADD(b.end_date, INTERVAL 1 DAY) > :start_blk
        )
    ";
    $st2 = $pdo->prepare($sql2);
    $st2->bindValue(':start_rules', $start);
    $st2->bindValue(':end_rules',   $end);
    $st2->bindValue(':start_res',   $start);
    $st2->bindValue(':end_res',     $end);
    $st2->bindValue(':start_blk',   $start);
    $st2->bindValue(':end_blk',     $end);
    if ($series !== '')                      $st2->bindValue(':series',   $series);
    if ($maxPrice !== null && $maxPrice > 0) $st2->bindValue(':maxPrice', $maxPrice);
    $st2->execute();
    $ignMin = $st2->fetchAll(PDO::FETCH_ASSOC);

    $meta = ['nights'=>$nights];

    if ($ignMin) {
        $mins = array_map(fn($r)=>(int)$r['required_min'], $ignMin);
        $minRequiredGlobal = min($mins);
        $suggestedEnd = (clone $d1)->modify("+$minRequiredGlobal day")->format('Y-m-d'); // end exclusivo

        $meta['no_results_reason'] = 'min_nights';
        $meta['min_required']      = $minRequiredGlobal;
        $meta['suggested_end']     = $suggestedEnd;
    } else {
        $meta['no_results_reason'] = 'occupied';
    }

    echo json_encode(['ok'=>true, 'count'=>0, 'campers'=>[], 'meta'=>$meta], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}
