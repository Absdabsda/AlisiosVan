<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require_once '/home/u647357107/domains/alisiosvan.com/secure/bootstrap.php';
require __DIR__.'/../config/db.php';
$pdo = get_pdo();

header('Content-Type: application/json; charset=utf-8');

// Auth admin por ?key=...
$key = $_GET['key'] ?? '';
$adminKey = env('ADMIN_KEY','');
if (!$key || !hash_equals($adminKey, (string)$key)) {
    http_response_code(403);
    echo json_encode(['error'=>'forbidden']);
    exit;
}

/*
  Devolvemos eventos tipo FullCalendar:
  [{ id, title, start, end, allDay, url, backgroundColor, borderColor, textColor, classNames }]
*/
$sql = "
  SELECT r.id, r.start_date, r.end_date, r.status, r.manage_token,
         c.name AS camper
  FROM reservations r
  JOIN campers c ON c.id = r.camper_id
  WHERE r.end_date >= CURDATE() - INTERVAL 60 DAY
  ORDER BY r.start_date ASC
";
$st = $pdo->query($sql);

// Base URL (usa PUBLIC_BASE_URL si existe)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dir    = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$base   = rtrim(env('PUBLIC_BASE_URL', "$scheme://$host$dir"), '/');

$events = [];
while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
    $start = (new DateTime($row['start_date']))->format('Y-m-d');
    // end exclusivo (+1 día para eventos allDay)
    $endEx = (new DateTime($row['end_date']))->modify('+1 day')->format('Y-m-d');

    // Colores según estado
    $bg = '#80C1D0'; // paid (por defecto)
    if ($row['status'] === 'pending')                         $bg = '#CBB49E';
    if ($row['status'] === 'cancelled_by_customer' || $row['status'] === 'cancelled') $bg = '#EF476F';

    // Enlace a manage.php (abre en nueva pestaña)
    $url = null;
    if (!empty($row['manage_token'])) {
        $url = $base . '/manage.php?rid=' . (int)$row['id'] . '&t=' . urlencode($row['manage_token']);
    }

    $events[] = [
        'id'    => (string)$row['id'],
        'title' => ($row['status'] === 'cancelled_by_customer' || $row['status'] === 'cancelled'
                ? 'CANCELLED — ' : '') . ('#'.$row['id'].' · '.$row['camper']),
        'start' => $start,
        'end'   => $endEx,
        'allDay'=> true,
        'url'   => $url,
        'backgroundColor' => $bg,
        'borderColor'     => $bg,
        'textColor'       => '#ffffff',
        'classNames'      => (in_array($row['status'], ['cancelled_by_customer','cancelled'], true)) ? ['ev-cancelled'] : []
    ];
}

echo json_encode($events, JSON_UNESCAPED_UNICODE);
