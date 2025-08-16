<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__.'/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__.'/..')->safeLoad();
require __DIR__.'/../config/db.php';
$pdo = get_pdo();

header('Content-Type: application/json; charset=utf-8');

$key = $_GET['key'] ?? '';
if (!$key || $key !== ($_ENV['ADMIN_KEY'] ?? '')) {
    http_response_code(403); echo json_encode(['error'=>'forbidden']); exit;
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

$events = [];
while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
    $start = (new DateTime($row['start_date']))->format('Y-m-d');
    // end exclusivo (+1 día para eventos "allDay")
    $endEx = (new DateTime($row['end_date']))->modify('+1 day')->format('Y-m-d');

    // Colores según estado (usa tu paleta de estilos.css como referencia)
    $bg = '#80C1D0'; // paid
    if ($row['status'] === 'pending')              $bg = '#CBB49E';
    if ($row['status'] === 'cancelled_by_customer') $bg = '#EF476F';

    // Enlace a manage.php (abre en nueva pestaña)
    $url = null;
    if (!empty($row['manage_token'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dir    = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $base   = rtrim("$scheme://$host$dir", '/');
        $url    = $base . '/manage.php?rid=' . (int)$row['id'] . '&t=' . $row['manage_token'];
    }

    $ev = [
        'id'    => (string)$row['id'],
        'title' => ($row['status'] === 'cancelled_by_customer'
                ? 'CANCELLED — ' : '') . ('#'.$row['id'].' · '.$row['camper']),
        'start' => $start,
        'end'   => $endEx,
        'allDay'=> true,
        'url'   => $url,
        'backgroundColor' => $bg,
        'borderColor'     => $bg,
        'textColor'       => '#ffffff',
        'classNames'      => ($row['status'] === 'cancelled_by_customer') ? ['ev-cancelled'] : []
    ];
    $events[] = $ev;
}

echo json_encode($events, JSON_UNESCAPED_UNICODE);

