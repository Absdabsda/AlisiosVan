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

    // ---- INPUT -------------------------------------------------------------
    $input   = json_decode(file_get_contents('php://input'), true) ?? [];
    $camperId = (int)($input['camper_id'] ?? 0);
    $start    = $input['start'] ?? '';
    $end      = $input['end'] ?? '';
    if (!$camperId || !$start || !$end) throw new Exception('Missing data');

    // ---- CAMPER ------------------------------------------------------------
    $st = $pdo->prepare("SELECT * FROM campers WHERE id=?");
    $st->execute([$camperId]);
    $camper = $st->fetch(PDO::FETCH_ASSOC);
    if (!$camper) throw new Exception('Camper not found');

    // ---- NIGHTS / PRICES ---------------------------------------------------
    $d1 = new DateTime($start);
    $d2 = new DateTime($end);
    $nights = (int)$d1->diff($d2)->format('%a');
    if ($nights < 1) throw new Exception('Invalid date range');

    $unit = (float)$camper['price_per_night'];
    $depositCents = (int) round($unit * 100);                // cobro depósito = 1ª noche
    $totalCents   = (int) round($unit * 100 * $nights);      // total informativo

    // ---- RESERVA (pending) + manage token ---------------------------------
    $token = bin2hex(random_bytes(24)); // 48 chars
    $hasManage = false;
    try {
        $chk = $pdo->prepare("SHOW COLUMNS FROM `reservations` LIKE 'manage_token'");
        $chk->execute();
        $hasManage = (bool)$chk->fetch();
    } catch (Throwable $e) { /* ignore */ }

    if ($hasManage) {
        $st = $pdo->prepare("
          INSERT INTO reservations (camper_id, start_date, end_date, status, manage_token, created_at)
          VALUES (?, ?, ?, 'pending', ?, NOW())
        ");
        $st->execute([$camperId, $start, $end, $token]);
    } else {
        // fallback si aún no añadiste la columna
        $st = $pdo->prepare("
          INSERT INTO reservations (camper_id, start_date, end_date, status, created_at)
          VALUES (?, ?, ?, 'pending', NOW())
        ");
        $st->execute([$camperId, $start, $end]);
    }
    $reservationId = (int)$pdo->lastInsertId();

    // ---- BASE URL ----------------------------------------------------------
    // Usa PUBLIC_BASE_URL de tu .env en producción; cae a tu localhost si no está
    $base = rtrim($_ENV['PUBLIC_BASE_URL'] ?? 'http://localhost/CanaryVanGit/AlisiosVan/src', '/');
    $manageUrl = $base . '/manage.php?rid=' . $reservationId . '&t=' . $token;

    // ---- STRIPE ------------------------------------------------------------
    $stripe = new \Stripe\StripeClient($_ENV['STRIPE_SECRET']);

    error_log("nights=$nights unit={$unit} depositCents={$depositCents} totalCents={$totalCents}");

    $session = $stripe->checkout->sessions->create([
        'mode'        => 'payment',
        'success_url' => $base . '/thanks.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'  => $base . '/cancel.php?rid=' . $reservationId,

        // Línea: depósito (1ª noche)
        'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => sprintf(
                        "%s (%s) – Depósito 1ª noche (%d noches totales)",
                        $camper['name'],
                        $camper['series'],
                        $nights
                    ),
                    'description' => sprintf(
                        "Reserva %d · Fechas %s → %s · Importe total estimado €%0.2f (pago ahora solo el depósito)",
                        $reservationId, $start, $end, $totalCents / 100
                    ),
                ],
                'unit_amount' => $depositCents,
            ],
            'quantity' => 1,
        ]],

        // Recogida de datos para la factura
        'billing_address_collection' => 'required',
        'tax_id_collection'          => ['enabled' => true], // NIF/VAT si aplica
        // 'automatic_tax' => ['enabled' => true], // si usas Stripe Tax

        // Crea/guarda el Customer
        'customer_creation' => 'always',

        // Genera factura tras el pago
        'invoice_creation' => [
            'enabled' => true,
            'invoice_data' => [
                'metadata' => [
                    'reservation_id'        => (string)$reservationId,
                    'camper_id'             => (string)$camperId,
                    'start'                 => $start,
                    'end'                   => $end,
                    'nights'                => (string)$nights,
                    'price_per_night_eur'   => (string)$unit,
                    'total_due_cents'       => (string)$totalCents,
                    'deposit_cents'         => (string)$depositCents,
                    'deposit_type'          => 'first_night',
                    'manage_url'            => $manageUrl,
                ],
                'footer' => "Alisios Van · NIF X12345678 · Canarias · alisios.van@gmail.com",
            ],
        ],

        // Textos y consentimiento
        'consent_collection' => [
            'terms_of_service' => 'required',
        ],
        'custom_text' => [
            'submit' => ['message' => 'Pagarás ahora el depósito (1ª noche). El resto se abona en persona a la recogida.'],
            'terms_of_service_acceptance' => [
                'message' => 'Acepto los [Términos del servicio](https://alisiosvan/terms) y la [Política de privacidad](https://tu-dominio/privacidad).'
            ],
        ],

        // Metadata útil para backoffice
        'metadata' => [
            'reservation_id'        => (string)$reservationId,
            'camper_id'             => (string)$camperId,
            'start'                 => $start,
            'end'                   => $end,
            'nights'                => (string)$nights,
            'price_per_night_eur'   => (string)$unit,
            'total_due_cents'       => (string)$totalCents,
            'deposit_cents'         => (string)$depositCents,
            'deposit_type'          => 'first_night',
            'manage_url'            => $manageUrl,
            'manage_token'          => $token,
        ],
    ]);

    // Guarda el checkout id para webhooks/confirmación
    $st = $pdo->prepare("UPDATE reservations SET stripe_session_id=? WHERE id=?");
    $st->execute([$session->id, $reservationId]);

    echo json_encode(['ok'=>true,'url'=>$session->url]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
