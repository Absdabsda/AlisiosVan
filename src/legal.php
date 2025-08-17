<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Legal Notice | Alisios Van</title>

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
            <h1 class="page-hero__title">Legal Notice</h1>
        </div>
    </section>

    <section class="container py-4 py-md-5">
        <article class="legal-article">
            <h2>1. Website Owner</h2>
            <p>
                In compliance with Law 34/2002, of 11 July, on Information Society Services and Electronic Commerce (LSSI-CE), the following details are provided:
            </p>
            <ul>
                <li><strong>Owner:</strong> Carlos Enrique Rodríguez Pérez (trade name: Alisios Van)</li>
                <li><strong>Tax ID (NIF):</strong> 39492536H</li>
                <li><strong>Address:</strong> Calle Barcelona 50, Puerto del Rosario, 35600, Las Palmas, Spain</li>
                <li><strong>Email:</strong> <a href="mailto:alisios.van@gmail.com">alisios.van@gmail.com</a></li>
                <li><strong>Phone:</strong> <a href="tel:+34610136383">+34 610 136 383</a></li>
            </ul>

            <h2>2. Purpose</h2>
            <p>
                This website aims to present the services of Alisios Van for the rental of camper vans, as well as to provide information and a contact channel for clients and potential customers.
            </p>

            <h2>3. Access and use</h2>
            <p>
                Access to the website is free. Users agree to use the site in a lawful manner, in accordance with these terms and applicable regulations, and to refrain from carrying out any activity that may damage the image, interests or rights of the owner or third parties.
            </p>

            <h2>4. Intellectual and industrial property</h2>
            <p>
                All contents of this website (texts, images, logos, designs, source code, etc.) are owned by the owner or by third parties who have authorised their use. Reproduction, distribution, public communication or modification without prior written authorisation is prohibited.
            </p>

            <h2>5. Links policy</h2>
            <p>
                This website may include links to third-party sites. We are not responsible for their contents or the results that may arise from accessing them. The inclusion of links does not imply any association, merger or participation with the linked entities.
            </p>

            <h2>6. Liability</h2>
            <p>
                The owner is not responsible for misuse of the information on the website or for damages arising from such misuse.
                We do not guarantee the absence of interruptions or errors in access to the website, although we will make our best efforts to avoid them or correct them.
            </p>

            <h2>7. Applicable law and jurisdiction</h2>
            <p>
                These terms are governed by Spanish law. For any dispute, the parties submit to the Courts and Tribunals legally competent in the province of Las Palmas, unless a different jurisdiction is imperatively established by law.
            </p>

            <p class="small text-muted mt-4">Last updated: <?= date('F Y') ?></p>
        </article>
    </section>
</main>

<?php include 'inc/footer.inc'; ?>
</body>
</html>
