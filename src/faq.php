<?php
declare(strict_types=1);
require __DIR__ . '/../config/i18n-lite.php';
?>
<!doctype html>
<html lang="<?= htmlspecialchars($LANG ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __('FAQ | Alisios Van') ?></title>

    <meta name="google" content="notranslate">

    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flag-icons/css/flag-icons.min.css">

    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/faq.css">
    <link rel="stylesheet" href="css/cookies.css">
    <script src="js/header.js" defer></script>
    <script src="js/faq.js" defer></script>
    <script src="js/cookies.js" defer></script>

    <style>
        :root { --header-bg-rgb: 82,118,159; } /* #52769F */
    </style>
</head>
<body>
<?php include 'inc/header.inc'; ?>

<main>
    <!-- HERO -->
    <section class="page-hero faq-hero pos-center">
        <div class="page-hero__content">
            <h1 class="page-hero__title"><?= __('Frequently Asked Questions') ?></h1>
        </div>
    </section>

    <!-- Tools -->
    <section class="container py-4">
        <div class="faq-tools">
            <div class="faq-search">
                <div class="search">
                    <i class="bi bi-search"></i>
                    <input id="faqSearch" class="form-control form-control-lg pe-5" type="search"
                           placeholder="<?= __('Search: insurance, ferry, pickup') ?>" autocomplete="off" />
                </div>
            </div>
            <div class="faq-meta">
                <span id="faqCount" class="text-muted"><?= __('Showing all') ?></span>
                <div class="d-flex gap-2">
                    <button id="expandAll" class="btn btn-sm btn-outline-secondary" type="button"><?= __('Expand all') ?></button>
                    <button id="collapseAll" class="btn btn-sm btn-outline-secondary" type="button"><?= __('Collapse all') ?></button>
                </div>
            </div>
        </div>

        <div class="faq-chips mt-3">
            <button class="chip active" data-tag=""><?= __('All') ?></button>
            <button class="chip" data-tag="booking"><?= __('Booking') ?></button>
            <button class="chip" data-tag="drivers"><?= __('Drivers') ?></button>
            <button class="chip" data-tag="insurance"><?= __('Insurance') ?></button>
            <button class="chip" data-tag="payments"><?= __('Payments') ?></button>
            <button class="chip" data-tag="pickup"><?= __('Pickup/Return') ?></button>
            <button class="chip" data-tag="vehicle"><?= __('Vehicle') ?></button>
            <button class="chip" data-tag="camping"><?= __('Camping') ?></button>
            <button class="chip" data-tag="rules"><?= __('Rules') ?></button>
            <button class="chip" data-tag="extras"><?= __('Extras') ?></button>
        </div>
    </section>

    <!-- Two-column accordion -->
    <section class="container pb-5">
        <div id="faqGrid" class="faq-columns">
            <!-- LEFT -->
            <div class="faq-col" data-col="left">
                <details class="faq-item" id="faq-included" data-tags="booking,vehicle">
                    <summary><?= __('What does the camper rental include?') ?></summary>
                    <div class="content">
                        <p><?= __('Bed setup, basic kitchen kit (stove, cookware, utensils), fridge, exterior shower, interior lighting, and cleaning kit.') ?></p>
                        <p><?= __('Some models add an awning and an outdoor table and chairs.') ?></p>
                    </div>
                </details>

                <details class="faq-item" id="faq-license" data-tags="drivers">
                    <summary><?= __('Do I need a special driving licence?') ?></summary>
                    <div class="content"><?= __('No. A valid category B car licence is enough. Please bring your physical licence and ID/passport.') ?></div>
                </details>

                <details class="faq-item" id="faq-age" data-tags="drivers">
                    <summary><?= __('What is the minimum driver age?') ?></summary>
                    <div class="content">
                        <?= __('26+ with at least 3 years of valid driving experience. Case-by-case exceptions may be considered with prior notice—please contact us in advance. An extra fee may apply depending on the case. Additional drivers can be added (bring their documents too).') ?>
                    </div>
                </details>

                <details class="faq-item" id="faq-mileage" data-tags="vehicle,booking">
                    <summary><?= __('Is there a mileage limit?') ?></summary>
                    <div class="content">
                        <?= __('We include 150 km/day. Extra kilometres can be purchased for a small fee. Let us know if you’re planning a long route.') ?>
                    </div>
                </details>

                <details class="faq-item" id="faq-pets" data-tags="rules,vehicle">
                    <summary><?= __('Are pets allowed?') ?></summary>
                    <div class="content">
                        <ul>
                            <li><?= __('Yes, well-behaved pets are welcome. You must notify us when booking.') ?></li>
                            <li><?= __('Return the camper clean and in good condition.') ?></li>
                            <li><?= __('If you do not notify us in advance, the full security deposit will be retained.') ?></li>
                        </ul>
                    </div>
                </details>

                <details class="faq-item" id="faq-times" data-tags="pickup">
                    <summary><?= __('What are the pickup and return times?') ?></summary>
                    <div class="content"><?= __('Pickup from 15:00 and return by 12:00 (flexible when possible). Late returns may incur a fee.') ?></div>
                </details>

                <details class="faq-item" id="faq-fuel" data-tags="vehicle">
                    <summary><?= __('What is the fuel policy?') ?></summary>
                    <div class="content"><?= __('Full-to-full: we deliver with a full tank and you return it full. Keep the fuel receipt if asked.') ?></div>
                </details>

                <details class="faq-item" id="faq-cleaning" data-tags="rules,vehicle">
                    <summary><?= __('What cleaning is expected on return?') ?></summary>
                    <div class="content">
                        <ul>
                            <li><?= __('Return dishes clean, interior tidy, and grey/dirty water tanks emptied.') ?></li>
                            <li><?= __('If this is not done, an additional cleaning fee may be charged.') ?></li>
                            <li><?= __('You do not need to fill the clean water tanks or wash the exterior — we do it to protect windows and the solar panel.') ?></li>
                            <li><?= __('If you wash the exterior yourself, do not use high-pressure water directly on windows or the solar panel.') ?></li>
                        </ul>
                    </div>
                </details>
            </div>

            <!-- RIGHT -->
            <div class="faq-col" data-col="right">
                <details class="faq-item" id="faq-insurance" data-tags="insurance">
                    <summary><?= __('Is insurance included?') ?></summary>
                    <div class="content"><?= __('Driving insurance is included in the booking price, with 24/7 roadside assistance.') ?></div>
                </details>

                <details class="faq-item" id="faq-deposit" data-tags="payments,insurance">
                    <summary><?= __('Is there a security deposit?') ?></summary>
                    <div class="content"><?= __('Yes, a refundable deposit is required at pickup. Returned after inspection if there’s no damage or pending fines.') ?></div>
                </details>

                <details class="faq-item" id="faq-ferry" data-tags="booking,camping">
                    <summary><?= __('Can I take the ferry to other islands?') ?></summary>
                    <div class="content">
                        <p><?= __('Yes — but only after speaking with the administrator and coordinating how to do it, because specific conditions must be followed.') ?></p>
                        <ul class="mt-2 mb-0">
                            <li><?= __('Prior approval is required (route and dates).') ?></li>
                            <li><?= __('You must follow the operator’s rules and protect the vehicle during boarding.') ?></li>
                        </ul>
                    </div>
                </details>

                <details class="faq-item" id="faq-sleep" data-tags="camping,rules">
                    <summary><?= __('Where can I sleep/camp?') ?></summary>
                    <div class="content">
                        <ul>
                            <li><?= __('Respect local regulations: only stay overnight in authorised areas and campsites.') ?></li>
                            <li><?= __('Do not access protected areas or private land; penalties can be high.') ?></li>
                            <li><?= __('Our geolocation systems help ensure your adventure is safe and responsible.') ?></li>
                        </ul>
                    </div>
                </details>

                <details class="faq-item" id="faq-dirt" data-tags="vehicle,rules">
                    <summary><?= __('Can I drive on dirt roads?') ?></summary>
                    <div class="content">
                        <p><?= __('All these rules are monitored by our geolocation systems.') ?></p>
                        <ul>
                            <li><?= __('With our retro campers and vans it is forbidden to drive on dirt roads, sand, beaches or off-road areas.') ?></li>
                            <li><?= __('Exception: 4x4 models may use light, well-maintained tracks at low speed.') ?></li>
                            <li><?= __('Maximum speed: 30 km/h on dirt roads or authorised areas.') ?></li>
                            <li><?= __('Prohibited: leaving the track or accessing protected areas.') ?></li>
                            <li><?= __('Consequences: repeatedly exceeding the speed limit or entering restricted areas will result in full retention of the deposit; any legal penalties will be the driver’s sole responsibility.') ?></li>
                        </ul>
                    </div>
                </details>

                <details class="faq-item" id="faq-power" data-tags="vehicle">
                    <summary><?= __('What about electricity and charging?') ?></summary>
                    <div class="content"><?= __('The house battery powers lights, fridge, and USB. Some vans include an inverter or 230V when connected at campsites.') ?></div>
                </details>

                <details class="faq-item" id="faq-breakdown" data-tags="insurance,vehicle">
                    <summary><?= __('What if I have a breakdown?') ?></summary>
                    <div class="content">
                        <p><?= __('Always contact us first.') ?></p>
                        <ul>
                            <li><?= __('24/7 roadside assistance is included.') ?></li>
                            <li><?= __('Some breakdowns may prevent the trip from continuing.') ?></li>
                            <li><?= __('If it is not your responsibility, you may:') ?>
                                <ul>
                                    <li><?= __('Get a refund for the unused days, or') ?></li>
                                    <li><?= __('Continue with a replacement vehicle.') ?></li>
                                </ul>
                            </li>
                            <li><?= __('Failure to follow our instructions or acting on your own will result in the retention of the security deposit.') ?></li>
                        </ul>
                    </div>
                </details>

                <details class="faq-item" id="faq-payment-cancel" data-tags="payments,booking">
                    <summary><?= __('Payments and cancellation policy') ?></summary>
                    <div class="content">
                        <ul>
                            <li><?= __('We accept major credit and debit cards.') ?></li>
                            <li><?= __('A deposit confirms your reservation; the balance is paid before pickup.') ?></li>
                            <li><?= __('Cancellation policy:') ?>
                                <ul>
                                    <li><?= __('If we cancel, you receive 100% of your reservation back, and we are released from any further liability.') ?></li>
                                    <li><?= __('If you cancel more than 1 month in advance, 50% of the booking deposit is refunded (to cover admin and payment processing costs).') ?></li>
                                    <li><?= __('If you cancel less than 1 month in advance, the reservation is non-refundable.') ?></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </details>
            </div>
        </div>

        <p id="faqEmpty" class="text-muted d-none mt-4"><?= __('No results for your search.') ?></p>
    </section>
</main>

<?php include 'inc/footer.inc'; ?>
</body>
</html>
