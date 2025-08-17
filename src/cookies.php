<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Cookie Policy | Alisios Van</title>

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
    <link rel="stylesheet" href="css/cookies.css">

    <script src="js/header.js" defer></script>

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
            <h1 class="page-hero__title">Cookie Policy</h1>
        </div>
    </section>

    <section class="container py-4 py-md-5">
        <article class="legal-article">
            <p class="lead">
                This policy explains what cookies are, which ones we use on this website and how you can manage them.
            </p>

            <div class="d-flex gap-2 flex-wrap mb-4">
                <a class="btn btn-outline-secondary" href="#" onclick="cookieConsent?.openSettings();return false;">
                    Cookie settings
                </a>
                <a class="btn btn-outline-secondary" href="contacto.php">Contact us</a>
            </div>

            <h2 id="what-are-cookies">1. What are cookies</h2>
            <p>
                Cookies are small files downloaded to your device when you access certain web pages, allowing, among other things,
                the storage and retrieval of information about your browsing habits.
            </p>

            <h2 id="types">2. Types of cookies we use</h2>
            <p>At Alisios Van, we use:</p>
            <ul>
                <li><strong>Technical cookies</strong>: necessary for the proper functioning of the website.</li>
                <li><strong>Personalization cookies</strong>: remember preferences such as language or region.</li>
                <li><strong>Analytics cookies (statistics)</strong>: allow us to count users and analyse how the website is used
                    (only activated if accepted by the user).</li>
            </ul>
            <p>We currently do not use cookies for personalized advertising.</p>

            <h2 id="consent">3. Consent</h2>
            <p>
                The installation of non-essential cookies is carried out only with the prior consent of the user, requested via a
                pop-up notice upon accessing the website. This notice allows the user to:
            </p>
            <ul>
                <li>Accept all cookies.</li>
                <li>Reject all cookies.</li>
                <li>Configure cookies to choose which ones to accept.</li>
            </ul>

            <h2 id="manage">4. Disabling cookies</h2>
            <p>
                You can allow, block or delete cookies installed on your device through your browser settings. The steps vary by
                browser and device.
            </p>

            <h2 id="table">5. Cookies used on this site</h2>
            <p class="text-muted small mb-2">
                The following list is indicative and may change as we improve the website.
                Analytics cookies are only set if you accept them in the banner.
            </p>

            <div class="table-responsive">
                <table class="table table-sm align-middle legal-table">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Provider</th>
                        <th>Purpose</th>
                        <th>Type</th>
                        <th>Duration</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>PHPSESSID</td>
                        <td>Alisios Van (first-party)</td>
                        <td>Maintains the user’s session and basic site functionality.</td>
                        <td>Essential</td>
                        <td>Session</td>
                    </tr>
                    <tr>
                        <td>cookie consent (localStorage: <code>cc_v1</code>)</td>
                        <td>Alisios Van (first-party)</td>
                        <td>Stores your cookie choices so we don’t show the banner again.</td>
                        <td>Preference</td>
                        <td>Up to 12 months</td>
                    </tr>
                    <tr>
                        <td>_ga, _ga_*</td>
                        <td>Google Analytics (first-party)</td>
                        <td>Analytics — helps us understand how visitors use the site.</td>
                        <td>Analytics (opt-in)</td>
                        <td>Up to 2 years</td>
                    </tr>
                    <tr>
                        <td>_gid</td>
                        <td>Google Analytics (first-party)</td>
                        <td>Distinguishes users for statistics.</td>
                        <td>Analytics (opt-in)</td>
                        <td>24 hours</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <h2 id="more">6. More information</h2>
            <p>
                For more information about the use of cookies on this website, you can contact us at
                <a href="mailto:alisios.van@gmail.com">alisios.van@gmail.com</a>.
            </p>

            <p class="small text-muted mt-4">
                Last updated: <?= date('F Y') ?>
            </p>
        </article>
    </section>
</main>

<?php include 'inc/footer.inc'; ?>
</body>
</html>
