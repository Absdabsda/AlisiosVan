<?php
declare(strict_types=1);
header('Content-Type: application/json');
ini_set('display_errors','0');

try {
    // --- Autoload & env ----------------------------------------------------
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload)) throw new Exception("No autoload");
    require $autoload;

    Dotenv\Dotenv::createImmutable(__DIR__.'/..')->safeLoad();
    if (empty($_ENV['STRIPE_SECRET'])) throw new Exception('STRIPE_SECRET missing');

    require __DIR__ . '/../config/db.php';
    $pdo = get_pdo();

    // ---- INPUT -------------------------------------------------------------
    $input    = json_decode(file_get_contents('php://input'), true) ?? [];
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

    $unit         = (float)$camper['price_per_night'];
    $depositCents = (int) round($unit * 100);           // deposit = first night
    $totalCents   = (int) round($unit * 100 * $nights); // estimated total

    // ---- RESERVATION (pending) + manage token ------------------------------
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
        $st = $pdo->prepare("
          INSERT INTO reservations (camper_id, start_date, end_date, status, created_at)
          VALUES (?, ?, ?, 'pending', NOW())
        ");
        $st->execute([$camperId, $start, $end]);
    }
    $reservationId = (int)$pdo->lastInsertId();

    // ---- BASE URL ----------------------------------------------------------
    // Use PUBLIC_BASE_URL in production; fallback to localhost in dev
    $base = rtrim($_ENV['PUBLIC_BASE_URL'] ?? 'http://localhost/CanaryVanGit/AlisiosVan/src', '/');
    $manageUrl = $base . '/manage.php?rid=' . $reservationId . '&t=' . $token;

    // ---- STRIPE ------------------------------------------------------------
    $stripe = new \Stripe\StripeClient($_ENV['STRIPE_SECRET']);

    // Shortened copies for strings
    $nameLine = sprintf(
        '%s (%s) – Booking deposit (first night) · %d nights total',
        $camper['name'],
        $camper['series'],
        $nights
    );
    $descLine = sprintf(
        'Reservation #%d · Dates %s → %s · Estimated total €%0.2f. You are paying only the deposit (first night) now; the remaining balance is due at pick-up (cash or PayPal). Cancellation policy: if we cancel → full refund; if you cancel → deposit is non-refundable.',
        $reservationId, $start, $end, $totalCents / 100
    );

    $session = $stripe->checkout->sessions->create([
        'mode'        => 'payment',
        'client_reference_id' => (string)$reservationId,
        'success_url' => $base . '/thanks.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'  => $base . '/cancel.php?rid=' . $reservationId,

        // Force Stripe Checkout UI language to English
        'locale'      => 'en',

        // Line item: deposit (first night)
        'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name'        => $nameLine,
                    'description' => $descLine,
                ],
                'unit_amount' => $depositCents,
            ],
            'quantity' => 1,
        ]],

        // Collect details for the invoice
        'billing_address_collection' => 'required',
        'tax_id_collection'          => ['enabled' => true],

        // Create/save the Customer
        'customer_creation' => 'always',

        // Create invoice after payment
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
                // Put cancellation info in the invoice footer too
                'footer' => "Alisios Van · Canary Islands · alisios.van@gmail.com · Cancellation: company cancellations → full refund; customer cancellations → deposit non-refundable.",
            ],
        ],

        // Terms & custom text on Checkout
        'consent_collection' => [
            'terms_of_service' => 'required',
        ],
        'custom_text' => [
            'submit' => [
                'message' => 'You are paying the booking deposit (first night). The remaining balance is paid in person at pick-up (cash or PayPal).'
            ],
            'terms_of_service_acceptance' => [
                // Keep it short; link to your full policy
                'message' => 'I accept the [Terms & Cancellation Policy](https://alisiosvan.com/terms). Company cancellations: full refund. Customer cancellations: deposit is non-refundable.'
            ],
        ],

        // Metadata useful for backoffice
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

    // Save the checkout id for webhooks/confirmation
    $st = $pdo->prepare("UPDATE reservations SET stripe_session_id=? WHERE id=?");
    $st->execute([$session->id, $reservationId]);

    echo json_encode(['ok'=>true,'url'=>$session->url]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
