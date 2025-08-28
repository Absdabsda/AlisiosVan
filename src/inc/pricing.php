<?php
// src/inc/pricing.php

function nightly_price(PDO $pdo, int $camperId, string $ymd): float {
    // Busca regla que cubra ese día; si no, usa el precio base del camper
    $st = $pdo->prepare("
        SELECT price_per_night
        FROM camper_price_rules
        WHERE camper_id = ? AND start_date <= ? AND end_date >= ?
        ORDER BY start_date DESC, id DESC
        LIMIT 1
    ");
    $st->execute([$camperId, $ymd, $ymd]);
    $p = $st->fetchColumn();
    if ($p !== false) return (float)$p;

    $st = $pdo->prepare("SELECT price_per_night FROM campers WHERE id=?");
    $st->execute([$camperId]);
    return (float)$st->fetchColumn();
}

/** Suma noche a noche en [start, end) — end EXCLUSIVA (fecha de salida) */
function sum_price_range(PDO $pdo, int $camperId, string $startYmd, string $endYmd): float {
    $d    = new DateTime($startYmd);
    $last = new DateTime($endYmd);
    $total = 0.0;
    while ($d < $last) {
        $total += nightly_price($pdo, $camperId, $d->format('Y-m-d'));
        $d->modify('+1 day');
    }
    return $total;
}

/** Precio “de etiqueta” para el listado (el de la primera noche del rango) */
function price_label_for_range(PDO $pdo, int $camperId, string $startYmd): float {
    return nightly_price($pdo, $camperId, (new DateTime($startYmd))->format('Y-m-d'));
}
