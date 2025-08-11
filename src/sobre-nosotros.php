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
    <script src="js/header.js" defer></script>
    <script src="js/campers.js" defer></script>

    <style>
        :root { --header-bg-rgb: 37,50,48; } /* #253230 */
    </style>

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
                        In <strong>Alisios Van</strong>, we believe that travelling is a lifestyle. Our passion for freedom, nature and unique experiences guided us to create a project where each camper is an extension of our philosophy: comfortable, simple and with soul.
                    </p>
                    <p>
                        We're a small business located by the coast, in love with routes enjoyed with leisure, sunsets in front of the sea and improvised getaways. We care about the essentials: offering a close service, transparent and authentic.
                    </p>
                    <p>
                        Our campers are thought for people like you, who long to live intensely, without complications, with style and comfort.
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
                    <p>To provide unique camper travel experiences, connecting people with nature and the freedom to explore.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="valor-box">
                    <h3>Our Vision</h3>
                    <p>To be leaders in camper rentals in the Canary Islands, promoting sustainable, flexible, and conscious tourism.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="valor-box">
                    <h3>Our Values</h3>
                    <p>Passion for adventure, customer commitment, respect for the environment, and a personal touch in every interaction.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bloque final Sobre Nosotros -->
    <section class="about-final-block">
        <div class="container about-final-container">
            <div class="about-final-image">
                <img src="img/carlos.jpeg" alt="Preparando nuestra camper">
            </div>
            <div class="about-final-text">
                <h4>Made with care, made for you.</h4>
                <p>Each Alisios Van camper is lovingly prepared by our team, ensuring every detail is ready for your next adventure.
                    We believe in slow, conscious travel – enjoying the journey as much as the destination.</p>
                <p>From maintenance to cleaning, we put our hearts into making sure your camper feels like home on wheels.</p>
                <a href="contacto.php" class="btn">Start your journey</a>
            </div>
        </div>
    </section>


</main>

<?php include 'inc/footer.inc'; ?>
</body>
</html>
