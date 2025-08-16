<?php
// /api/camper.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$campers = [
    1 => [
        'id' => 1,
        'name' => 'Matcha',
        'series' => 'T3',
        'price_per_night' => 115,
        'seats' => 4,
        'images' => [
            'img/carousel/matcha-surf.34.32.jpeg',
            'img/carousel/t3-azul-mar.webp',
            'img/carousel/t3-azul-playa.webp',
        ],
        'badges' => ['Manual','Diesel','Fridge','Solar','2-3 sleeps'],
        'amenities' => ['Bed 140cm','Portable shower','Kitchen kit','Camping table & chairs','USB chargers','Bluetooth speaker'],
        'description' => 'Our green T3 ready for island adventures.'
    ],
    2 => [
        'id' => 2,
        'name' => 'Skye',
        'series' => 'T3',
        'price_per_night' => 100,
        'seats' => 4,
        'images' => [
            'img/carousel/t3-azul-playa.webp',
            'img/carousel/t3-azul-mar.webp',
            'img/carousel/t4-sol.webp',
        ],
        'badges' => ['Manual','Diesel','Fridge','2-3 sleeps'],
        'amenities' => ['Bed 140cm','Portable shower','Kitchen kit','Camping table & chairs','USB chargers'],
        'description' => 'Blue T3 with ocean vibes.'
    ],
    3 => [
        'id' => 3,
        'name' => 'Rusty',
        'series' => 'T4',
        'price_per_night' => 85,
        'seats' => 4,
        'images' => [
            'img/carousel/t4-sol.webp',
            'img/carousel/t3-azul-mar.webp',
            'img/carousel/t3-azul-playa.webp',
        ],
        'badges' => ['Manual','Diesel','Fridge','2-3 sleeps'],
        'amenities' => ['Bed 140cm','Portable shower','Kitchen kit','Camping table & chairs','USB chargers'],
        'description' => 'T4 comfy and budget friendly.'
    ],
];

if (!$id || !isset($campers[$id])) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Camper not found']);
    exit;
}

echo json_encode(['ok' => true, 'camper' => $campers[$id]]);
