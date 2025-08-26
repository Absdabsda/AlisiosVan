<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require_once __DIR__ . '/../config/bootstrap_env.php';
require __DIR__ . '/../config/db.php';
$pdo = get_pdo();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

/* ===== Auth por cookie ===== */
$adminKeyEnv = env('ADMIN_KEY','');
$cookieKey   = $_COOKIE['admin_key'] ?? '';
if (!$adminKeyEnv || !$cookieKey || !hash_equals($adminKeyEnv, (string)$cookieKey)) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ===== Base URL ===== */
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dir    = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
$base   = rtrim(env('PUBLIC_BASE_URL', "$scheme://$host$dir"), '/');

$events = [];

/* === Reservas === */
$sql = "
  SELECT r.id, r.start_date, r.end_date, r.status, r.manage_token,
         c.name AS camper
  FROM reservations r
  JOIN campers c ON c.id = r.camper_id
  WHERE r.end_date >= CURDATE() - INTERVAL 60 DAY
  ORDER BY r.start_date ASC
";
$st = $pdo->query($sql);

while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
    $start = (new DateTime($row['start_date']))->format('Y-m-d');
    $endEx = (new DateTime($row['end_date']))->modify('+1 day')->format('Y-m-d'); // allDay end exclusivo

    $isCancelled = in_array($row['status'], ['cancelled','cancelled_by_customer','cancelled_by_admin'], true);
    $bg = $row['status'] === 'pending' ? '#CBB49E' : ($isCancelled ? '#EF476F' : '#80C1D0');

    $url = $base . '/manage-admin.php?rid=' . (int)$row['id']; // admin view

    $events[] = [
        'id'    => 'res-'.$row['id'],
        'title' => ($isCancelled ? 'CANCELLED — ' : '') . '#' . (int)$row['id'] . ' · ' . (string)$row['camper'],
        'start' => $start,
        'end'   => $endEx,
        'allDay'=> true,
        'url'   => $url,
        'backgroundColor' => $bg,
        'borderColor'     => $bg,
        'textColor'       => '#ffffff',
        'classNames'      => $isCancelled ? ['ev-cancelled'] : []
    ];
}

/* === Bloqueos === */
$sqlB = "
  SELECT b.id, b.start_date, b.end_date, b.reason,
         c.name AS camper, c.id AS camper_id
  FROM blackout_dates b
  JOIN campers c ON c.id = b.camper_id
  WHERE b.end_date >= CURDATE() - INTERVAL 60 DAY
  ORDER BY b.start_date ASC
";
$stB = $pdo->query($sqlB);

while ($row = $stB->fetch(PDO::FETCH_ASSOC)) {
    $start = (new DateTime($row['start_date']))->format('Y-m-d');
    $endEx = (new DateTime($row['end_date']))->modify('+1 day')->format('Y-m-d'); // inclusivo → +1

    $title = 'BLOQUEO — ' . (string)$row['camper'];
    if (!empty($row['reason'])) $title .= ' · ' . (string)$row['reason'];

    $events[] = [
        'id'    => 'blk-'.$row['id'],
        'title' => $title,
        'start' => $start,
        'end'   => $endEx,
        'allDay'=> true,
        'url'   => null,
        'backgroundColor' => '#60666d',
        'borderColor'     => '#60666d',
        'textColor'       => '#ffffff',
        'classNames'      => ['ev-block']
    ];
}

echo json_encode($events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
