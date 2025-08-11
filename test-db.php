<?php
require __DIR__ . '/config/db.php';

$campers = db()->query('SELECT id, slug, name, series, price_per_night FROM campers ORDER BY id')->fetchAll();
$stats   = db()->query('SELECT status, COUNT(*) n FROM bookings GROUP BY status')->fetchAll();

header('Content-Type: text/plain; charset=utf-8');
echo "OK: conexión a la BD\n\nCampers:\n";
foreach ($campers as $c) {
    echo "- {$c['id']} {$c['name']} ({$c['series']}) {$c['price_per_night']}€/noche\n";
}
echo "\nReservas por estado:\n";
foreach ($stats as $s) {
    echo "- {$s['status']}: {$s['n']}\n";
}
