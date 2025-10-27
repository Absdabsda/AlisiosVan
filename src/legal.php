<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

// importante: ruta desde src/ a config/
require __DIR__ . '/../config/i18n-lite.php';
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= __('Legal Notice | Alisios Van') ?></title>

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

    <link rel="stylesheet" href="/src/css/estilos.css">
    <link rel="stylesheet" href="/src/css/header.css">
    <link rel="stylesheet" href="/src/css/legal.css">
    <link rel="stylesheet" href="/src/css/cookies.css">

    <script src="/src/js/header.js" defer></script>
    <script src="/src/js/cookies.js" defer></script>

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
            <h1 class="page-hero__title"><?= __('Legal Notice') ?></h1>
        </div>
    </section>

    <section class="container py-4 py-md-5">
        <article class="legal-article">
            <h2><?= __('1. Website Owner') ?></h2>
            <p>
                <?= __('In compliance with Law 34/2002 of 11 July on Information Society Services and Electronic Commerce (LSSI-CE), the following details are provided:') ?>
            </p>
            <ul>
                <li><strong><?= __('Owner:') ?></strong> <?= __('Alisios Experience S.L (in formation)') ?></li>
                <li><strong><?= __('Address:') ?></strong> <?= __('Calle Sevilla 11, Puerto del Rosario, 35600, Las Palmas, Spain') ?></li>
                <li><strong><?= __('Email:') ?></strong> <a href="mailto:alisios.van@gmail.com">alisios.van@gmail.com</a></li>
                <li><strong><?= __('Phone:') ?></strong> <a href="tel:+34610136383">+34 610 136 383</a></li>
            </ul>

            <h2><?= __('2. Purpose of the website') ?></h2>
            <p><?= __('The website aims to:') ?></p>
            <ul>
                <li><?= __('Present the services of Alisios Van for camper van rental.') ?></li>
                <li><?= __('Provide general information about the company and its services.') ?></li>
                <li><?= __('Offer a contact and booking channel for customers and interested persons.') ?></li>
            </ul>

            <h2><?= __('3. Access and use') ?></h2>
            <p><?= __('Access to the website is free. The user agrees to use it lawfully, in accordance with these terms and applicable regulations, and to refrain from activities that may:') ?></p>
            <ul>
                <li><?= __('Damage the image, interests or rights of the owner or third parties.') ?></li>
                <li><?= __('Introduce viruses or malicious programs.') ?></li>
                <li><?= __('Attempt to access other accounts or systems on the site without authorization.') ?></li>
            </ul>

            <h2><?= __('4. Intellectual and industrial property') ?></h2>
            <p><?= __('All website contents (texts, images, logos, designs, source code, photographs, icons, videos, etc.) are owned by the owner or by third parties who have authorized their use.') ?></p>
            <p><?= __('Reproduction, distribution, public communication, transformation or modification is prohibited without prior written authorization.') ?></p>
            <p><?= __('Authorized use must always respect the rights of the owner and third parties.') ?></p>

            <h2><?= __('5. Links policy') ?></h2>
            <p><?= __('This site may include links to third-party websites.') ?></p>
            <p><?= __('Alisios Van is not responsible for the contents, services or results derived from accessing those sites.') ?></p>
            <p><?= __('The inclusion of links does not imply association, merger or participation with the linked entities.') ?></p>

            <h2><?= __('6. Liability') ?></h2>
            <p><?= __('The owner is not responsible for the improper use of the information on the website or for damages arising from such use.') ?></p>
            <p><?= __('We do not guarantee the absence of interruptions, errors or technical failures, although reasonable means will be applied to prevent or correct them.') ?></p>
            <p><?= __('The information published is for informational purposes and does not replace direct consultation with the company regarding rental conditions, prices or availability.') ?></p>

            <h2><?= __('7. Protection of data and geolocation') ?></h2>
            <p><?= __('Data collected through contact or booking forms are managed in accordance with our Privacy Policy.') ?></p>
            <p><?= __('Our campers are equipped with geolocation and driving control systems, used exclusively to:') ?></p>
            <ul>
                <li><?= __('Ensure the safety of the vehicle and the user.') ?></li>
                <li><?= __('Monitor speed limits and the legal circulation perimeter.') ?></li>
                <li><?= __('Prevent access to protected areas with high penalties.') ?></li>
            </ul>
            <p><?= __('Failure to comply with these rules may entail retention of the security deposit and the driver’s legal liability.') ?></p>

            <h2><?= __('8. Applicable law and jurisdiction') ?></h2>
            <p><?= __('These terms are governed by Spanish law.') ?></p>
            <p><?= __('For any dispute arising from the use of the website or services, the parties submit to the Courts and Tribunals of the province of Las Palmas, unless a different jurisdiction is imperatively established by law.') ?></p>

            <p class="small text-muted mt-4"><?= __('Last updated:') ?> <?= date('F Y') ?></p>
        </article>
    </section>
</main>

<?php include 'inc/footer.inc'; ?>
</body>
</html>
