<?php
declare(strict_types=1);
header('Content-Type: application/json');

ini_set('display_errors','0');

try {
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload)) throw new Exception("No autoload");
    require $autoload;

    Dotenv\Dotenv::createImmutable(__DIR__.'/../env')->safeLoad();
    if (empty($_ENV['STRIPE_SECRET'])) throw new Exception('STRIPE_SECRET missing');

    require __DIR__ . '/../config/db.php';
    $pdo = get_pdo();

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $camperId = (int)($input['camper_id'] ?? 0);
    $start = $input['start'] ?? '';
    $end   = $input['end'] ?? '';
    if (!$camperId || !$start || !$end) throw new Exception('Missing data');

    // Datos del camper
    $st = $pdo->prepare("SELECT * FROM campers WHERE id=?");
    $st->execute([$camperId]);
    $camper = $st->fetch(PDO::FETCH_ASSOC);
    if (!$camper) throw new Exception('Camper not found');

    // Nº noches
    $d1 = new DateTime($start);
    $d2 = new DateTime($end);
    $nights = (int)$d1->diff($d2)->format('%a');
    if ($nights < 1) throw new Exception('Invalid date range');

    $unit = (float)$camper['price_per_night'];

    // cobrar solo la 1ª noche
    $depositCents = (int) round($unit * 100);

    // Guarda también el total “informativo”
    $totalCents = (int) round($unit * 100 * $nights);

    // Crea fila de reserva "pending" (hold 30 min por created_at)
    $st = $pdo->prepare("
      INSERT INTO reservations (camper_id, start_date, end_date, status, created_at)
      VALUES (?, ?, ?, 'pending', NOW())
  ");
    $st->execute([$camperId, $start, $end]);
    $reservationId = (int)$pdo->lastInsertId();

    // Stripe
    $stripe = new \Stripe\StripeClient($_ENV['STRIPE_SECRET']);

    $base = 'http://localhost/CanaryVanGit/AlisiosVan/src'; // ajusta en prod

    // Antes de crear la sesión
    error_log("nights=$nights unit={$unit} depositCents={$depositCents} totalCents={$totalCents}");

    $session = $stripe->checkout->sessions->create([
        'mode' => 'payment',
        'success_url' => $base . '/thanks.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'  => $base . '/cancel.php?rid=' . $reservationId,

        'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    // depósito de 1ª noche
                    'name' => sprintf(
                        "%s (%s) – Depósito 1ª noche (%d noches totales)",
                        $camper['name'],
                        $camper['series'],
                        $nights
                    ),
                ],
                'unit_amount' => $depositCents, // << solo 1 noche
            ],
            'quantity' => 1,
        ]],

        'consent_collection' => [
            'terms_of_service' => 'required',
        ],
        'custom_text' => [
            'submit' => ['message' => 'Pagarás ahora el depósito (1ª noche). El resto se abona en persona a la recogida.'],
            'terms_of_service_acceptance' => [
                'message' => 'Acepto los [Términos del servicio](https://tu-dominio/terminos) y la [Política de privacidad](https://tu-dominio/privacidad).'
            ],
        ],


        // Metadata útil para el webhook/backoffice
        'metadata' => [
            'reservation_id' => (string)$reservationId,
            'camper_id' => (string)$camperId,
            'start' => $start,
            'end' => $end,
            'nights' => (string)$nights,
            'price_per_night_eur' => (string)$unit,
            'total_due_cents' => (string)$totalCents,
            'deposit_cents' => (string)$depositCents,
            'deposit_type' => 'first_night',
        ],

    ]);

    // guarda checkout id para el webhook
    $st = $pdo->prepare("UPDATE reservations SET stripe_session_id=? WHERE id=?");
    $st->execute([$session->id, $reservationId]);

    echo json_encode(['ok'=>true,'url'=>$session->url]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
