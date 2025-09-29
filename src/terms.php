<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);
require __DIR__ . '/../config/i18n-lite.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= __('Terms of Service | Alisios Van') ?></title>

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
    <link rel="stylesheet" href="css/legal.css">

    <script src="js/header.js" defer></script>

    <style>
        :root { --header-bg-rgb: 133,126,110; } /* #857E6E */
    </style>
</head>
<body>
<?php include __DIR__ . '/inc/header.inc'; ?>

<main>
    <!-- HERO -->
    <section class="page-hero legal-hero pos-center">
        <div class="page-hero__content">
            <h1 class="page-hero__title"><?= __('Terms of Service') ?></h1>
        </div>
    </section>

    <section class="container py-4 py-md-5">
        <article class="legal-article">
            <p class="lead">
                <?= __('These Terms of Service (“Terms”) govern the booking and rental of campervan experiences from Alisios Van. By submitting a booking request or using our services, you agree to these Terms.') ?>
            </p>

            <h2><?= __('1. Who we are') ?></h2>
            <ul>
                <li><strong><?= __('Owner:') ?></strong> <?= __('Alisios Experience S.L (in formation)') ?></li>
                <li><strong><?= __('Address:') ?></strong> <?= __('Calle Sevilla 11, Puerto del Rosario, 35600, Las Palmas, Spain') ?></li>
                <li><strong><?= __('Email:') ?></strong> <a href="mailto:alisios.van@gmail.com">alisios.van@gmail.com</a></li>
                <li><strong><?= __('Phone:') ?></strong> <a href="tel:+34610136383">+34 610 136 383</a></li>
            </ul>

            <h2><?= __('2. Eligibility & drivers') ?></h2>
            <ul>
                <li><strong><?= __('Minimum age:') ?></strong> <?= __('26+ years. We may accept younger drivers case by case with prior communication and approval; an extra fee may apply.') ?></li>
                <li><strong><?= __('Experience:') ?></strong> <?= __('at least 3 years of valid driving experience.') ?></li>
                <li><strong><?= __('Licence:') ?></strong> <?= __('Category B (car). Bring your physical driving licence and ID/passport.') ?></li>
                <li><?= __('Additional drivers are allowed if identified and approved before pickup (bring their documents too).') ?></li>
            </ul>

            <h2><?= __('3. Booking, payment & deposit') ?></h2>
            <ul>
                <li><?= __('A booking deposit confirms the reservation; the balance is due before or at pickup (as specified in your confirmation).') ?></li>
                <li><?= __('A refundable security deposit is required at pickup and is returned after inspection if there is no damage, missing items, fuel differences, cleaning charges or fines/tolls pending.') ?></li>
                <li><?= __('Prices include basic insurance and the daily mileage shown below; extras are available on request.') ?></li>
            </ul>

            <h2><?= __('4. Mileage, fuel & usage') ?></h2>
            <ul>
                <li><strong><?= __('Mileage:') ?></strong> <?= __('150 km per day included. Extra kilometres can be purchased for a small fee — ask us if you plan a long route.') ?></li>
                <li><strong><?= __('Fuel policy:') ?></strong> <?= __('full-to-full. We deliver with a full tank; return it full and keep the receipt if requested.') ?></li>
                <li><strong><?= __('Roads:') ?></strong> <?= __('only authorised paved roads are allowed. Driving on dirt tracks, sand, beaches or countryside areas is not permitted. If this rule is breached, the security deposit will be fully retained.') ?></li>
                <li><?= __('Traffic fines, tolls and penalties during the rental are the renter’s responsibility.') ?></li>
            </ul>

            <h2><?= __('5. Insurance & roadside assistance') ?></h2>
            <ul>
                <li><?= __('Basic insurance with third-party liability is included. Optional coverage may reduce the excess/deposit — see your quote.') ?></li>
                <li><strong><?= __('Breakdown:') ?></strong> <?= __('24/7 roadside assistance is included. Please call us first and we will help you and coordinate everything to make it easier for you.') ?></li>
                <li><?= __('Insurance does not cover misuse, off-road driving, overhead/underbody damage, lost keys, contamination of fuel or personal belongings.') ?></li>
            </ul>

            <h2><?= __('6. Pickup & return') ?></h2>
            <ul>
                <li><strong><?= __('Pickup:') ?></strong> <?= __('from 15:00 ·') ?> <strong><?= __('Return:') ?></strong> <?= __('by 12:00 (flexible when possible). Unscheduled late returns incur a €40 fee per extra hour.') ?></li>
                <li><?= __('Pickup and return are usually in Puerto del Rosario (Fuerteventura). Alternative locations may be available on request.') ?></li>
            </ul>

            <h2><?= __('7. Cleaning & care') ?></h2>
            <ul>
                <li><?= __('Please return dishes clean, interior tidy, and tanks emptied (if applicable). Extra cleaning may be charged otherwise.') ?></li>
                <li><?= __('No smoking inside the vehicle. Reasonable cleaning fees apply for smoke or strong odours.') ?></li>
                <li><?= __('Report any incident or damage as soon as it occurs.') ?></li>
            </ul>

            <h2><?= __('8. Pets') ?></h2>
            <p><?= __('Well-behaved pets are welcome. Please notify us when booking and return the camper clean and in good condition.') ?></p>

            <h2><?= __('9. Ferries & inter-island travel') ?></h2>
            <p><?= __('Ferry travel between islands is usually allowed with prior notice and approval. Failure to notify in advance will result in total loss of the security deposit.') ?></p>

            <h2><?= __('10. What’s included') ?></h2>
            <p><?= __('Each camper includes, at minimum:') ?></p>
            <ul>
                <li><?= __('Bed set-up, basic kitchen kit (hob, cookware, utensils), fridge, outdoor shower, interior lighting, and cleaning kit.') ?></li>
                <li><?= __('Some models include awning, outdoor table/chairs. See the specific camper page for details.') ?></li>
            </ul>

            <h2><?= __('11. Cancellations & changes') ?></h2>
            <ul>
                <li><?= __('Cancellation terms depend on notice/season and are shown in your booking confirmation or quote.') ?></li>
                <li><?= __('Date or model changes are subject to availability and any price difference in effect at the time of change.') ?></li>
                <li><?= __('No-show or early return may be charged as per the confirmation terms.') ?></li>
            </ul>

            <h2><?= __('12. Prohibited uses') ?></h2>
            <p><?= __('The camper must not be used for illegal activities, races, towing, sub-rental or any purpose not covered by the insurance. Do not carry more passengers than seats or exceed load limits.') ?></p>
            <p><?= __('Note: under current regulations, all sand tracks have a 30 km/h speed limit and are monitored by drones to prevent access to protected areas. Our campers have a location and driving control system to help ensure compliance. If speed limits are repeatedly exceeded or restricted areas are accessed, the security deposit will be fully retained and any resulting legal penalties will be the driver’s sole responsibility.') ?></p>

            <h2><?= __('13. Liability & limitation') ?></h2>
            <p><?= __('To the extent permitted by law, Alisios Van is not liable for indirect, incidental or consequential losses. Nothing in these Terms excludes liability that cannot be excluded by law.') ?></p>

            <h2><?= __('14. Personal data') ?></h2>
            <p>
                <?= __('We process personal data in accordance with our') ?>
                <a href="politica.php"><?= __('Privacy Policy') ?></a>.
                <?= __('You can manage cookie preferences at any time from') ?>
                <a href="#" onclick="cookieConsent?.openSettings();return false;"><?= __('Cookie settings') ?></a>.
            </p>

            <h2><?= __('15. Governing law & jurisdiction') ?></h2>
            <p><?= __('These Terms are governed by Spanish law. For any dispute, the parties submit to the competent Courts and Tribunals in the province of Las Palmas, unless a different jurisdiction is imperatively established by law.') ?></p>

            <h2><?= __('16. Changes to these terms') ?></h2>
            <p><?= __('We may update these Terms to reflect changes in our service or legal requirements. The version published on this website is the one in force.') ?></p>

            <p class="small text-muted mt-4"><?= __('Last updated:') ?> <?= date('F Y') ?></p>
        </article>
    </section>
</main>

<?php include __DIR__ . '/inc/footer.inc'; ?>
</body>
</html>
