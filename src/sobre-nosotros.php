<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>About Us | Alisios Van</title>

    <link href="https://fonts.googleapis.com/css2?family=Amatic+SC:wght@400;700&family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Seaweed+Script&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Rock+Salt&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" defer></script>

    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <!-- Añade esto en <head> para Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/sobre-nosotros.css">
    <link rel="stylesheet" href="css/cookies.css">

    <script src="js/campers.js" defer></script>
    <script src="js/cookies.js" defer></script>


</head>
<body>
<?php include 'inc/header.inc'; ?>
<main>
    <section class="page-hero about-us-hero">
        <div class="page-hero__content">
            <h1 class="page-hero__title">About Us</h1>
        </div>
    </section>

    <!-- Texto explicativo Sobre Nosotros -->
    <div class="sobre-nosotros container">
        <div class="contenido">
            <div class="texto">
                <p>
                    At <strong>Alisios Van</strong>, we believe travel is a way of life. Our love of freedom, nature, and one-of-a-kind experiences inspired us to create a project where every camper reflects our philosophy: comfort, simplicity, and soulful.
                </p>
                <p>
                    We’re a small, coast-based company in love with unhurried routes, sunsets over the sea, and spontaneous getaways. We focus on what matters: offering a warm, transparent and authentic service.
                </p>
                <p>
                    Our campers are designed for people like you who want to live fully, keep things simple, and travel in comfort and style.
                </p>
            </div>
        </div>
    </div>

    <!-- Fichas valores corporativos -->
    <div class="valores-corporativos container">
        <div class="row justify-content-center text-center g-4">
            <div class="col-md-4">
                <div class="valor-box">
                    <h3>Our Mission</h3>
                    <p>To create memorable campervan journeys that connect people with nature and the freedom to explore.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="valor-box">
                    <h3>Our Vision</h3>
                    <p>To be the leading campervan rental in the Canary Islands, championing sustainable, flexible, and mindful travel.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="valor-box">
                    <h3>Our Values</h3>
                    <p>Passion for adventure, commitment to our guests, respect for the environment, and a personal touch in every detail.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bloque final Sobre Nosotros -->
    <section class="about-final-block">
        <div class="container about-final-container">
            <div class="about-final-image">
                <img src="img/carlos.jpeg" alt="Preparing our camper van">
            </div>
            <div class="about-final-text">
                <h4>Made with care, made for you.</h4>
                <p>Every Alisios Van is prepared with care by our team so every detail is ready for your next adventure. We believe in slow, mindful travel enjoying the journey as much as the destination.</p>
                <p>From maintenance to cleaning, we put our hearts into making sure your camper feels like a home on wheels.</p>
                <a href="contacto.php" class="btn">Tell us about your trip</a>
            </div>
        </div>
    </section>

</main>

<?php include 'inc/footer.inc'; ?>
</body>
</html>
