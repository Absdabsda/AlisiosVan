<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Privacy Policy | Alisios Van</title>

    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/legal.css">

    <script src="js/header.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" defer></script>

    <style>
        :root { --header-bg-rgb: 133,126,110; } /* #857E6E */
    </style>
</head>
<body>
<?php include 'inc/header.inc'; ?>

<main>
    <!-- HERO -->
    <section class="page-hero legal-hero pos-center">
        <div class="page-hero__content">
            <h1 class="page-hero__title">Privacy Policy</h1>
        </div>
    </section>

    <section class="container py-4 py-md-5">
        <article class="legal-article">
            <p class="lead">
                At Alisios Van, we respect the privacy of our users and are committed to protecting the personal data you share with us.
            </p>

            <h2>1. Data Controller</h2>
            <ul>
                <li><strong>Owner:</strong> Carlos Enrique Rodríguez Pérez (trade name: Alisios Van)</li>
                <li><strong>Tax ID (NIF):</strong> 39492536H</li>
                <li><strong>Address:</strong> Calle Barcelona 50, Puerto del Rosario, 35600, Las Palmas, Spain</li>
                <li><strong>Email:</strong> <a href="mailto:alisios.van@gmail.com">alisios.van@gmail.com</a></li>
            </ul>

            <h2>2. Personal data we collect</h2>
            <p>
                Data you provide through our contact or booking forms, such as: name, email, phone, preferred model, travel dates and your message.
                If you complete a reservation, we may receive booking details necessary to manage the service. Payments are processed by secure payment providers; we do not store full card data on our servers.
            </p>

            <h2>3. Purpose of data processing</h2>
            <ul>
                <li>To manage inquiries and requests.</li>
                <li>To process camper van rental bookings and provide customer support.</li>
                <li>To maintain necessary business communications and administrative messages.</li>
            </ul>

            <h2>4. Legal basis</h2>
            <p>
                The processing is based on the <strong>consent</strong> you give us when submitting a form, and, where applicable,
                on the <strong>execution of a contract</strong> (booking and rental). We may also rely on <strong>legitimate interest</strong> for security, fraud prevention and basic analytics of site performance (only if you consent to analytics cookies).
            </p>

            <h2>5. Data retention</h2>
            <p>
                We keep your data while there is a contractual or business relationship and for the periods required by applicable law (e.g., tax and accounting obligations). Afterwards, data may be securely blocked only for legal liabilities.
            </p>

            <h2>6. Disclosure to third parties</h2>
            <p>
                We do not share your data with third parties, except when legally required or when necessary to provide the contracted service (for example, vehicle insurance or roadside assistance companies), as well as technology providers that host our website, email or customer communications, under the appropriate data processing agreements.
            </p>

            <h2>7. International transfers</h2>
            <p>
                Some providers may be located outside the European Economic Area. Where this happens, we ensure adequate safeguards such as Standard Contractual Clauses or equivalent legal mechanisms.
            </p>

            <h2>8. User rights</h2>
            <p>
                You may exercise your rights of <strong>access</strong>, <strong>rectification</strong>, <strong>deletion</strong>,
                <strong>objection</strong>, <strong>restriction</strong> and <strong>portability</strong> by emailing
                <a href="mailto:alisios.van@gmail.com">alisios.van@gmail.com</a>. Please include sufficient information to verify your identity.
                If you believe your data is being processed in violation of current regulations, you have the right to lodge a complaint with the
                <a href="https://www.aepd.es/" target="_blank" rel="noopener">Spanish Data Protection Agency (AEPD)</a>.
            </p>

            <h2>9. Cookies and tracking</h2>
            <p>
                We use technical cookies and, with your consent, preference and analytics cookies. For details and to manage your choices, see our
                <a href="cookies.php">Cookie Policy</a>. You can change your consent at any time from
                <a href="#" onclick="cookieConsent?.openSettings();return false;">Cookie settings</a>.
            </p>

            <h2>10. Security</h2>
            <p>
                We apply appropriate technical and organisational measures to protect your data against unauthorised access, alteration, disclosure or destruction.
            </p>

            <h2>11. Updates to this policy</h2>
            <p>
                We may update this policy to reflect changes in our practices or legal requirements. We encourage you to review it periodically.
            </p>

            <p class="small text-muted mt-4">Last updated: <?= date('F Y') ?></p>
        </article>
    </section>
</main>

<?php include 'inc/footer.inc'; ?>
</body>
</html>
