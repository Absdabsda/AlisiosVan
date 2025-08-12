<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">

    <title>Log in | Alisios Van</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amatic+SC:wght@400;700&family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Seaweed+Script&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Rock+Salt&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" defer></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>

    <!-- Font -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <!-- Styles -->
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/auth.css">

    <!-- Scripts -->
    <script src="js/header.js" defer></script>
    <script src="js/auth.js" defer></script>
</head>

<body>
<?php include 'inc/header.inc'; ?>

<main class="auth-split" data-auth-context="login"
      style="--auth-media-image:url('../img/skye-horizontal.JPG')">

    <!-- IMAGEN -->
    <aside class="auth-media lift-under-header" aria-hidden="true">
        <div class="auth-media__overlay">
            <h2 class="auth-media__title">Welcome back</h2>
            <p class="auth-media__subtitle">Pick up where you left off</p>
        </div>
    </aside>

    <!--FORMULARIO -->
    <section class="auth-panel under-header">
        <div class="auth-card">
            <h1 class="custom-title mb-3">Log in</h1>
            <p class="text-muted mb-4">Access your bookings and profile.</p>

            <!-- Login form -->
            <form method="post" action="actions/login_process.php" class="auth-form">
                <div class="row g-3">
                    <!-- Email -->
                    <div class="col-12">
                        <label for="email" class="form-label">Email Address</label>
                        <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control"
                                placeholder="youremail@example.com"
                                autocomplete="email"
                                required>
                    </div>

                    <!-- Password -->
                    <div class="col-12">
                        <label for="password" class="form-label">Password</label>
                        <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                placeholder="Your password"
                                autocomplete="current-password"
                                minlength="8"
                                required>
                    </div>

                    <!-- Remember + Forgot -->
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        <div>
                            <a href="olvidoContrasenya.php" class="auth-link">Forgot your password?</a>
                        </div>
                    </div>
                </div>

                <!-- CTA -->
                <button type="submit" class="btn btn-primary w-100 mt-4">Log in</button>

                <!-- Enlace a registro, transición -->
                <div class="text-center mt-3">
                    <a
                            href="register.php"
                            class="auth-switch auth-link"
                            data-target="registro.php">
                        Create an account
                    </a>
                </div>
            </form>

            <!-- Contacto - Sección de ayuda -->
            <div class="auth-meta mt-4 text-center">
                <small class="text-muted">
                    Need help? <a href="contact.php">Contact us</a>
                </small>
            </div>
        </div>
    </section>
</main>

<?php include 'inc/footer.inc'; ?>
</body>
</html>
