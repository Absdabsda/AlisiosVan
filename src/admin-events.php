<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require_once __DIR__ . '/../config/bootstrap_env.php';
require __DIR__ . '/../config/db.php';
$pdo = get_pdo();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Auth admin por ?key=...
$key = $_GET['key'] ?? '';
$adminKey = env('ADMIN_KEY','');
if (!$key || !hash_equals($adminKey, (string)$key)) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Base URL (usa PUBLIC_BASE_URL si existe)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dir    = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
$base   = rtrim(env('PUBLIC_BASE_URL', "$scheme://$host$dir"), '/');

$events = [];

/* === 1) Reservas === */
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

    if ($row['status'] === 'pending') {
        $bg = '#CBB49E';
    } elseif ($isCancelled) {
        $bg = '#EF476F';
    } else {
        $bg = '#80C1D0';
    }

    $url = null;
    if (!empty($row['manage_token'])) {
        $url = $base . '/manage.php?rid=' . (int)$row['id']
            . '&t=' . urlencode($row['manage_token'])
            . '&key=' . urlencode($adminKey);
    }

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

/* === Bloqueos (con blackout_dates) === */
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
    // end inclusivo en BD → +1 día para allDay
    $endEx = (new DateTime($row['end_date']))->modify('+1 day')->format('Y-m-d');

    $title = 'BLOQUEO — ' . (string)$row['camper'];
    if (!empty($row['reason'])) $title .= ' · ' . (string)$row['reason'];

    $events[] = [
        'id'    => 'blk-'.$row['id'],
        'title' => $title,
        'start' => $start,
        'end'   => $endEx,
        'allDay'=> true,
        'url'   => null,
        'backgroundColor' => '#60666d', // gris
        'borderColor'     => '#60666d',
        'textColor'       => '#ffffff',
        'classNames'      => ['ev-block']
    ];
}

echo json_encode($events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
