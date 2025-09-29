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
    <title><?= __('Privacy Policy | Alisios Van') ?></title>

    <!-- evita traducción automática de Chrome -->
    <meta name="google" content="notranslate">

    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap JS -->
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
            <h1 class="page-hero__title"><?= __('Privacy Policy') ?></h1>
        </div>
    </section>

    <section class="container py-4 py-md-5">
        <article class="legal-article">
            <p class="lead">
                <?= __('At Alisios Van, we respect the privacy of our users and are committed to protecting the personal data you share with us.') ?>
            </p>

            <h2><?= __('1. Data Controller') ?></h2>
            <ul>
                <li><strong><?= __('Owner:') ?></strong> <?= __('Alisios Experience S.L (in formation)') ?></li>
                <li><strong><?= __('Address:') ?></strong> <?= __('Calle Sevilla 11, Puerto del Rosario, 35600, Las Palmas, Spain') ?></li>
                <li><strong><?= __('Email:') ?></strong> <a href="mailto:alisios.van@gmail.com">alisios.van@gmail.com</a></li>
            </ul>

            <h2><?= __('2. Personal data we collect') ?></h2>
            <p><?= __('We collect data you provide through contact or booking forms, such as: name, email, phone, camper model, travel dates and your message. If you complete a booking, we may also collect data necessary to manage the rental and invoicing. Payments are processed by secure providers; we do not store full card details on our servers.') ?></p>
            <p><?= __('In addition, our campers have geolocation and driving control systems that record:') ?></p>
            <ul>
                <li><?= __('Location during the rental period.') ?></li>
                <li><?= __('Vehicle speed.') ?></li>
                <li><?= __('Compliance with the legal circulation perimeter and access restrictions to protected areas.') ?></li>
            </ul>

            <h2><?= __('3. Purpose of data processing') ?></h2>
            <ul>
                <li><?= __('Manage inquiries and requests.') ?></li>
                <li><?= __('Process bookings and provide customer support.') ?></li>
                <li><?= __('Ensure proper use of the vehicles by monitoring speed and permitted circulation areas, for user safety and fleet protection.') ?></li>
                <li><?= __('Maintain necessary administrative communications for service provision.') ?></li>
            </ul>

            <h2><?= __('4. Legal basis') ?></h2>
            <p><?= __('Processing is based on:') ?></p>
            <ul>
                <li><?= __('Consent you give us when submitting forms or accepting the booking.') ?></li>
                <li><?= __('Performance of a contract, i.e., provision of the rental service.') ?></li>
                <li><?= __('Legitimate interest, limited to vehicle security, damage prevention, fraud prevention, and basic website analytics (only if you consent to analytics cookies).') ?></li>
            </ul>

            <h2><?= __('5. Data retention') ?></h2>
            <p><?= __('We keep your data while there is a contractual or business relationship and for the applicable legal periods (tax, accounting or legal obligations). After this period, data may be securely blocked solely to address legal liabilities.') ?></p>

            <h2><?= __('6. Disclosure to third parties') ?></h2>
            <p><?= __('We do not share your data with third parties except:') ?></p>
            <ul>
                <li><?= __('When required by law.') ?></li>
                <li><?= __('To provide the contracted service (e.g., insurers, roadside assistance).') ?></li>
                <li><?= __('Technology providers that manage our website, email and communications, under data processing agreements.') ?></li>
            </ul>

            <h2><?= __('7. International transfers') ?></h2>
            <p><?= __('Some providers may be located outside the European Economic Area. In such cases, we apply adequate safeguards, such as Standard Contractual Clauses or equivalent legal mechanisms.') ?></p>

            <h2><?= __('8. User rights') ?></h2>
            <p>
                <?= __('You can exercise your rights of access, rectification, deletion, objection, restriction and portability by emailing') ?>
                <a href="mailto:alisios.van@gmail.com">alisios.van@gmail.com</a>,
                <?= __('including sufficient information to verify your identity.') ?>
                <?= __('If you believe your data is being processed in breach of current regulations, you have the right to lodge a complaint with the') ?>
                <a href="https://www.aepd.es/" target="_blank" rel="noopener"><?= __('Spanish Data Protection Agency (AEPD)') ?></a>.
            </p>

            <h2><?= __('9. Cookies and tracking') ?></h2>
            <p>
                <?= __('We use technical cookies and, with your consent, preference and analytics cookies. For details and to manage your choices, see our') ?>
                <a href="cookies.php"><?= __('Cookie Policy') ?></a>.
                <?= __('You can change your consent at any time from') ?>
                <a href="#" onclick="cookieConsent?.openSettings();return false;"><?= __('Cookie settings') ?></a>.
            </p>

            <h2><?= __('10. Security') ?></h2>
            <p><?= __('We apply appropriate technical and organizational measures to protect your data against unauthorized access, alteration, disclosure or destruction. Geolocation data are used only for the purposes described and are processed securely.') ?></p>

            <h2><?= __('11. Consequences of non-compliance with driving rules') ?></h2>
            <p><?= __('The geolocation system allows us to monitor compliance with:') ?></p>
            <ul>
                <li><?= __('Speed limit.') ?></li>
                <li><?= __('Legal circulation perimeter.') ?></li>
                <li><?= __('Access restrictions to protected areas.') ?></li>
            </ul>
            <p><?= __('In case of continued non-compliance with these rules:') ?></p>
            <ul>
                <li><?= __('The security deposit will be fully retained.') ?></li>
                <li><?= __('Any resulting legal penalties will be the sole responsibility of the driver.') ?></li>
            </ul>

            <h2><?= __('12. Updates to this policy') ?></h2>
            <p><?= __('We may update this policy to reflect changes in our practices or legal requirements. We encourage you to review it periodically.') ?></p>

            <p class="small text-muted mt-4"><?= __('Last updated:') ?> <?= date('F Y') ?></p>
        </article>
    </section>
</main>

<?php include __DIR__ . '/inc/footer.inc'; ?>
</body>
</html>
