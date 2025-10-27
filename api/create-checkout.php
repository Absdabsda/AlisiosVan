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

    // DB
    require __DIR__ . '/../config/db.php';
    $pdo = get_pdo();

    // ---- INPUT ----
    $input    = json_decode(file_get_contents('php://input'), true) ?? [];
    $camperId = (int)($input['camper_id'] ?? 0);
    $start    = $input['start'] ?? '';
    $end      = $input['end'] ?? '';
    if (!$camperId || !$start || !$end) throw new Exception('Missing data');

    // ---- CAMPER ----
    $st = $pdo->prepare("SELECT id, name, series, price_per_night FROM campers WHERE id=? LIMIT 1");
    $st->execute([$camperId]);
    $camper = $st->fetch(PDO::FETCH_ASSOC);
    if (!$camper) throw new Exception('Camper not found');

    // ---- NIGHTS ----
    $d1 = new DateTime($start);
    $d2 = new DateTime($end);
    $nights = (int)$d1->diff($d2)->format('%a');
    if ($nights < 1) throw new Exception('Invalid date range');

    $baseUnit = (float)$camper['price_per_night'];

    // ========= PRECIO CON RULES =========
    // Cargamos TODAS las rules del rango de una vez
    $q = $pdo->prepare("
        SELECT id, start_date, end_date, price_per_night
          FROM camper_price_rules
         WHERE camper_id = :cid
           AND NOT (end_date < :s OR start_date > :e)
         ORDER BY id DESC
    ");
    $q->execute([
        ':cid' => $camperId,
        ':s'   => (new DateTime($start))->format('Y-m-d'),
        ':e'   => (new DateTime($end))->format('Y-m-d'),
    ]);
    $rules = $q->fetchAll(PDO::FETCH_ASSOC);

    // Función: precio de una fecha concreta según la última regla que aplique; si no, base
    $priceFor = function(string $ymd) use ($rules, $baseUnit): float {
        foreach ($rules as $r) { // ya vienen ordenadas por id DESC (más reciente primero)
            if ($ymd >= $r['start_date'] && $ymd <= $r['end_date']) {
                return (float)$r['price_per_night'];
            }
        }
        return (float)$baseUnit;
    };

    // Sumar noche a noche: desde start (incl) hasta end (excl)
    $run = new DateTime($start);
    $last = new DateTime($end);
    $totalCents = 0;
    while ($run < $last) {
        $ymd = $run->format('Y-m-d');
        $totalCents += (int) round($priceFor($ymd) * 100);
        $run->modify('+1 day');
    }

    // Si por lo que sea no pudo calcular, fallback a base
    if ($totalCents <= 0) {
        $totalCents = (int) round($baseUnit * 100 * $nights);
    }

    // Depósito
    $depositPercent = max(1, min(100, (int)env('DEPOSIT_PERCENT', 20)));
    $depositCents   = (int) max(1, round($totalCents * $depositPercent / 100));

    // Para textos: “precio por noche” mostrado = media para que cuadre visualmente
    $avgUnit = ($nights > 0) ? ($totalCents / 100.0 / $nights) : $baseUnit;

    // ---- BASE pública para DEV/PROD (sin /src al final) ----
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Cuando sirves con php -S y dev-router.php, SCRIPT_NAME será /api/create-checkout.php
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/'); // p.ej. /api
    $appBase   = preg_replace('~/api$~i', '', $scriptDir);           // -> '' (raíz del host)

// Usa .env si existe; si no, construye con host + subcarpeta
    $publicRoot = rtrim(env('PUBLIC_BASE_URL', "$scheme://$host$appBase"), '/');

// Defensivo: si alguien dejó /src al final por error, quítalo
    $publicRoot = preg_replace('~/src/?$~i', '', $publicRoot);

// Idioma 2 letras
    $lang = strtolower(substr($GLOBALS['LANG'] ?? 'es', 0, 2));

    // ---- STRIPE ----
    $stripe = new \Stripe\StripeClient($stripeSecret);

    $lang = strtolower(substr($GLOBALS['LANG'] ?? 'en', 0, 2));
    $stripeLocale = ['es'=>'es','en'=>'en','de'=>'de','fr'=>'fr','it'=>'it','pt'=>'pt','nl'=>'nl'][$lang] ?? 'en';

    $nameLine = sprintf(
        __('%s (%s) – Booking deposit (%d%% of total) · %d nights total'),
        $camper['name'], $camper['series'], $depositPercent, $nights
    );

    $descLine = sprintf(
        __('Dates %s → %s · Estimated total €%s • You are paying only a %d%% deposit now; remaining balance at pick-up.'),
        $start, $end, number_format($totalCents / 100, 2, '.', ''), $depositPercent
    );

    $submitMsg = sprintf(
        __('You are paying a %d%% booking deposit now. The remaining balance is paid in person at pick-up.'),
        $depositPercent
    );

    $tosMsg = sprintf(
        __('I accept the [Terms & Cancellation Policy](%s). Company cancellations: full refund. Customer cancellations: deposit is non-refundable.'),
        'https://alisiosvan.com/terms'
    );

    $invoiceFooter = __('Alisios Van · Canary Islands · alisios.van@gmail.com · Cancellation: company cancellations → full refund; customer cancellations → deposit non-refundable.');

    $session = $stripe->checkout->sessions->create([
        'mode'        => 'payment',
        'success_url' => $publicRoot . '/' . rawurlencode($lang) . '/thanks/?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'  => $publicRoot . '/' . rawurlencode($lang) . '/cancel/',
        'locale'      => $stripeLocale,

        'payment_method_types' => ['card'],
        'payment_intent_data'  => [
            'capture_method' => 'automatic',
        ],

        'line_items' => [[
            'price_data' => [
                'currency'     => 'eur',
                'product_data' => [
                    'name'        => $nameLine,
                    'description' => $descLine,
                ],
                'unit_amount'  => $depositCents,
            ],
            'quantity' => 1,
        ]],

        'billing_address_collection' => 'required',
        'customer_creation'          => 'always',
        'invoice_creation' => [
            'enabled' => true,
            'invoice_data' => [
                'metadata' => [
                    'camper_id'            => (string)$camperId,
                    'start'                => $start,
                    'end'                  => $end,
                    'nights'               => (string)$nights,
                    // usamos media para evitar confusión visual
                    'price_per_night_eur'  => (string)round($avgUnit, 2),
                    // TOTAL con rules (clave para thanks.php)
                    'total_due_cents'      => (string)$totalCents,
                    'deposit_cents'        => (string)$depositCents,
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
            'camper_id'            => (string)$camperId,
            'start'                => $start,
            'end'                  => $end,
            'nights'               => (string)$nights,
            'price_per_night_eur'  => (string)round($avgUnit, 2),
            'total_due_cents'      => (string)$totalCents,
            'deposit_cents'        => (string)$depositCents,
            'lang'                 => $lang,
        ],
    ]);

    echo json_encode(['ok'=>true,'url'=>$session->url]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
