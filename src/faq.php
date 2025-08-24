<!doctype html>
<html lang="<?= htmlspecialchars($LANG ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FAQ | Alisios Van</title>

    <!-- evita traducción automática de Chrome -->
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

    <!-- Tools: search + chips + open/close all -->
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
                        <?= __('24+ with at least 2 years of driving experience. Exceptions may be considered case-by-case with prior notice—please contact us in advance. Extra drivers can be added (bring their documents too).') ?>
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
                    <div class="content"><?= __('Yes, well-behaved pets are welcome. Please notify us when booking and return the camper clean.') ?></div>
                </details>

                <details class="faq-item" id="faq-times" data-tags="pickup">
                    <summary><?= __('What are the pickup and return times?') ?></summary>
                    <div class="content"><?= __('Pickup from 15:00 and return by 11:00 (flexible when possible). Late returns may incur a fee.') ?></div>
                </details>

                <details class="faq-item" id="faq-fuel" data-tags="vehicle">
                    <summary><?= __('What is the fuel policy?') ?></summary>
                    <div class="content"><?= __('Full-to-full: we deliver with a full tank and you return it full. Keep the fuel receipt if asked.') ?></div>
                </details>

                <details class="faq-item" id="faq-cleaning" data-tags="rules,vehicle">
                    <summary><?= __('What cleaning is expected on return?') ?></summary>
                    <div class="content"><?= __('Please return dishes clean, interior tidy, and tanks emptied (if applicable). Extra cleaning may be charged otherwise.') ?></div>
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
                    <div class="content"><?= __('Respect local regulations. Rules vary by municipality and in protected areas.') ?></div>
                </details>

                <details class="faq-item" id="faq-dirt" data-tags="vehicle,rules">
                    <summary><?= __('Can I drive on dirt roads?') ?></summary>
                    <div class="content"><?= __('Light, well-maintained tracks are fine at low speed. Off-road or risky terrain is not allowed and voids coverage.') ?></div>
                </details>

                <details class="faq-item" id="faq-power" data-tags="vehicle">
                    <summary><?= __('What about electricity and charging?') ?></summary>
                    <div class="content"><?= __('The house battery powers lights, fridge, and USB. Some vans include an inverter or 230V when connected at campsites.') ?></div>
                </details>

                <details class="faq-item" id="faq-breakdown" data-tags="insurance,vehicle">
                    <summary><?= __('What if I have a breakdown?') ?></summary>
                    <div class="content"><?= __('24/7 roadside assistance is included. Call us first and we will help you and coordinate everything to make it easier for you.') ?></div>
                </details>

                <details class="faq-item" id="faq-payment-cancel" data-tags="payments,booking">
                    <summary><?= __('Payments and cancellation policy') ?></summary>
                    <div class="content">
                        <?= __('We accept major cards. A booking deposit confirms the reservation; the balance is due before pickup. Cancellation terms depend on notice/season—see your booking confirmation.') ?>
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
