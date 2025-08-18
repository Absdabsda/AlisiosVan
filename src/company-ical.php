<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require_once '/home/u647357107/domains/alisiosvan.com/secure/bootstrap.php';
require __DIR__.'/../config/db.php';
$pdo = get_pdo();

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="alisiosvan-company.ics"');

function admin_key_valid(): ?string {
    $env = env('ADMIN_KEY','');
    if (!$env) return null;
    $k = $_GET['key'] ?? ($_COOKIE['admin_key'] ?? '');
    return ($k && hash_equals($env, (string)$k)) ? (string)$k : null;
}
function public_base(): string {
    $env = rtrim(env('PUBLIC_BASE_URL',''), '/');
    if ($env) return $env;
    $sch = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return rtrim("$sch://$host$dir", '/');
}
function ics_escape(string $s): string {
    return str_replace(["\\","; ", ";", ",", "\r\n", "\n", "\r"], ["\\\\","\;","\;","\,","\\n","\\n",""], $s);
}
function ics_line(string $s): string {
    $s = ics_escape($s);
    $out = '';
    for ($i=0, $len=strlen($s); $i<$len; $i+=73) {
        $out .= ($i ? "\r\n " : '') . substr($s, $i, 73);
    }
    return $out;
}

$adminKey = admin_key_valid();
$base     = public_base();

$st = $pdo->query("
  SELECT r.id, r.start_date, r.end_date, r.status, r.manage_token, c.name AS camper
  FROM reservations r
  JOIN campers c ON c.id = r.camper_id
  WHERE r.status IN ('paid')  -- añade más estados si quieres
  ORDER BY r.start_date ASC
");

$lines = [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:-//Alisios Van//EN',
    'CALSCALE:GREGORIAN',
    'METHOD:PUBLISH',
    'X-WR-CALNAME:Alisios Van Reservations'
];

while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
    $start = (new DateTime($row['start_date']))->format('Ymd');
    $endEx = (new DateTime($row['end_date']))->modify('+1 day')->format('Ymd'); // all-day exclusive
    $uid   = $row['id'].'@alisiosvan';

    // Prioridad: token de cliente; si no hay, link de admin si pasó la key
    $url = null;
    if (!empty($row['manage_token'])) {
        $url = $base.'/manage.php?rid='.(int)$row['id'].'&t='.rawurlencode($row['manage_token']);
    } elseif ($adminKey) {
        $url = $base.'/manage.php?rid='.(int)$row['id'].'&key='.rawurlencode($adminKey);
    }

    $summary = '#'.$row['id'].' · '.$row['camper'];
    $desc    = 'Status: '.$row['status'];

    $lines[] = 'BEGIN:VEVENT';
    $lines[] = 'UID:'.$uid;
    $lines[] = 'DTSTAMP:'.gmdate('Ymd\THis\Z');
    $lines[] = 'DTSTART;VALUE=DATE:'.$start;
    $lines[] = 'DTEND;VALUE=DATE:'.$endEx;
    $lines[] = ics_line('SUMMARY:'.$summary);
    $lines[] = ics_line('DESCRIPTION:'.$desc);
    if ($url) { $lines[] = ics_line('URL:'.$url); }
    $lines[] = 'END:VEVENT';
}

$lines[] = 'END:VCALENDAR';
echo implode("\r\n", $lines);
