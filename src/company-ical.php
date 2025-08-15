<?php
declare(strict_types=1);
require __DIR__.'/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__.'/../env')->safeLoad();
require __DIR__.'/../config/db.php';
$pdo = get_pdo();

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="alisiosvan-company.ics"');

$st = $pdo->query("
  SELECT r.id, r.start_date, r.end_date, r.status, c.name AS camper
  FROM reservations r
  JOIN campers c ON c.id = r.camper_id
  WHERE r.status IN ('paid') -- ajusta si quieres incluir otras
  ORDER BY r.start_date ASC
");

$lines = [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:-//Alisios Van//EN',
    'CALSCALE:GREGORIAN',
    'METHOD:PUBLISH'
];

while($row = $st->fetch(PDO::FETCH_ASSOC)){
    $start = (new DateTime($row['start_date']))->format('Ymd');
    $endEx = (new DateTime($row['end_date']))->modify('+1 day')->format('Ymd'); // all-day exclusive
    $uid   = $row['id'].'@alisiosvan';

    $lines[] = 'BEGIN:VEVENT';
    $lines[] = 'UID:'.$uid;
    $lines[] = 'DTSTAMP:'.gmdate('Ymd\THis\Z');
    $lines[] = 'DTSTART;VALUE=DATE:'.$start;
    $lines[] = 'DTEND;VALUE=DATE:'.$endEx;
    $lines[] = 'SUMMARY:Alisios Van · '.$row['camper'].' · #'.$row['id'];
    $lines[] = 'DESCRIPTION:Status: '.$row['status'];
    $lines[] = 'END:VEVENT';
}

$lines[] = 'END:VCALENDAR';
echo implode("\r\n", $lines);
