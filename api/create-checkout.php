<?php
declare(strict_types=1);
header('Content-Type: application/json');
ini_set('display_errors','0');

try {
    require_once __DIR__ . '/../config/bootstrap_env.php';
    require_once __DIR__ . '/../config/i18n-lite.php';
    require_once __DIR__.'/../src/inc/pricing.php';

    $stripeSecret = env('STRIPE_SECRET');
    if (!$stripeSecret) throw new Exception('STRIPE_SECRET missing');

    require __DIR__ . '/../config/db.php';
    $pdo = get_pdo();

    // ---- INPUT ----
    $input    = json_decode(file_get_contents('php://input'), true) ?? [];
    $camperId = (int)($input['camper_id'] ?? 0);
    $start    = $input['start'] ?? '';
    $end      = $input['end'] ?? '';
    if (!$camperId || !$start || !$end) throw new Exception('Missing data');

    // ---- CAMPER ----
    $st = $pdo->prepare("SELECT * FROM campers WHERE id=?");
    $st->execute([$camperId]);
    $camper = $st->fetch(PDO::FETCH_ASSOC);
    if (!$camper) throw new Exception('Camper not found');

    // ---- NIGHTS / PRICES ----
    $d1 = new DateTime($start);
    $d2 = new DateTime($end);
    $nights = (int)$d1->diff($d2)->format('%a');
    if ($nights < 1) throw new Exception('Invalid date range');

    $unit = (float)$camper['price_per_night'];

    $depositPercent = max(1, min(100, (int)env('DEPOSIT_PERCENT', 20))); // ← usa 20 por defecto
    $totalCents     = (int) round($unit * 100 * $nights);                 // total estimado
    $depositCents   = (int) max(1, round($totalCents * $depositPercent / 100));

    // ---- RESERVATION ----
    $token = bin2hex(random_bytes(24));
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

    // ---- BASE URL ----
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base   = rtrim(env('PUBLIC_BASE_URL', "$scheme://$host/src"), '/');

    $manageUrl = $base . '/manage.php?rid=' . $reservationId . '&t=' . $token;

    // ---- STRIPE ----
    $stripe = new \Stripe\StripeClient($stripeSecret);

    // Locale para Stripe (mapa sencillo)
    $lang = strtolower(substr($GLOBALS['LANG'] ?? 'en', 0, 2));
    $stripeLocale = [
        'es'=>'es','en'=>'en','de'=>'de','fr'=>'fr','it'=>'it','pt'=>'pt','nl'=>'nl'
    ][$lang] ?? 'en'; // o 'auto' si prefieres que Stripe detecte

    // Cadenas traducibles
    $nameLine = sprintf(
        __('%s (%s) – Booking deposit (%d%% of total) · %d nights total'),
        $camper['name'],
        $camper['series'],
        $depositPercent,
        $nights
    );

    $descLine = sprintf(
        __('Reservation #%d · Dates %s → %s · Estimated total €%s • You are paying only a %d%% deposit now; remaining balance at pick-up (cash or PayPal).'),
        $reservationId, $start, $end, number_format($totalCents / 100, 2, '.', ''), $depositPercent
    );

    $submitMsg = sprintf(
        __('You are paying a %d%% booking deposit now. The remaining balance is paid in person at pick-up (cash or PayPal).'),
        $depositPercent
    );

    $tosMsg = sprintf(
        __('I accept the [Terms & Cancellation Policy](%s). Company cancellations: full refund. Customer cancellations: deposit is non-refundable.'),
        'https://alisiosvan.com/terms'
    );
    $invoiceFooter = __('Alisios Van · Canary Islands · alisios.van@gmail.com · Cancellation: company cancellations → full refund; customer cancellations → deposit non-refundable.');

    $session = $stripe->checkout->sessions->create([
        'mode'        => 'payment',
        'client_reference_id' => (string)$reservationId,
        'success_url' => $base . '/thanks.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'  => $base . '/cancel.php?rid=' . $reservationId . '&t=' . $token,

        //en el idioma del sitio
        'locale'      => $stripeLocale,

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

        'billing_address_collection' => 'required',
        'tax_id_collection'          => ['enabled' => true],
        'customer_creation'          => 'always',

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
                'footer' => $invoiceFooter,
            ],
        ],

        'consent_collection' => [
            'terms_of_service' => 'required',
        ],
        'custom_text' => [
            'submit' => [ 'message' => $submitMsg ],
            'terms_of_service_acceptance' => [ 'message' => $tosMsg ],
        ],

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

    $st = $pdo->prepare("UPDATE reservations SET stripe_session_id=? WHERE id=?");
    $st->execute([$session->id, $reservationId]);

    echo json_encode(['ok'=>true,'url'=>$session->url]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
