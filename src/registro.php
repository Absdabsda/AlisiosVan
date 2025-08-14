<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">

    <title>Crear cuenta | Alisios Van</title>

    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Amatic+SC:wght@400;700&family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Seaweed+Script&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Rock+Salt&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">

    <!-- Iconos de Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />

    <!-- Bootstrap: CSS + JS (bundle con Popper) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" defer></script>

    <!-- Swiper: sólo necesario si hay carruseles en esta vista -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>

    <!-- Font Awesome (conjunto adicional de iconos) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/auth.css">

    <script src="js/header.js" defer></script>
    <script src="js/auth.js" defer></script>
</head>

<body>
<?php include 'inc/header.inc'; ?>

<main class="auth-split" data-auth-context="register"
      style="--auth-media-image:url('../img/skye-horizontal.JPG')">

    <!-- IZQUIERDA: bloque visual, decorativo (oculto a lectores de pantalla) -->
    <aside class="auth-media lift-under-header" aria-hidden="true">
        <div class="auth-media__overlay">
            <h2 class="auth-media__title">Freedom is not a place</h2>
            <p class="auth-media__subtitle">It’s the journey you choose</p>
        </div>
    </aside>

    <!-- DERECHA: panel con el formulario de registro -->
    <section class="auth-panel under-header">
        <div class="auth-card"> <!-- Tarjeta visual que contiene el formulario -->
            <h1 class="custom-title mb-3">Create Account</h1> <!-- Título visible -->
            <p class="text-muted mb-4">Join us to book your camper easily.</p> <!-- Subtítulo/claim -->

            <!--
              Formulario de registro:
              - method="post": envía datos vía POST
              - action="actions/register_process.php": endpoint PHP que procesa el alta
            -->
            <form method="post" action="actions/register_process.php" class="auth-form">
                <div class="row g-3"> <!-- Grid de Bootstrap con separación (g-3) -->
                    <!-- Nombre -->
                    <div class="col-12 col-md-6">
                        <label for="name" class="form-label">First Name</label>
                        <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control"
                                placeholder="Your first name"
                                autocomplete="given-name"
                        required>
                    </div>

                    <!-- Apellidos -->
                    <div class="col-12 col-md-6">
                        <label for="lastname" class="form-label">Last Name</label>
                        <input
                                type="text"
                                id="lastname"
                                name="lastname"
                                class="form-control"
                                placeholder="Your last name"
                                autocomplete="family-name"
                                required>
                    </div>

                    <!-- Teléfono -->
                    <div class="col-12">
                        <label for="phone" class="form-label">Phone Number</label>
                        <!-- Teclado numérico en móviles -->
                        <input
                                type="tel"
                                id="phone"
                                name="phone"
                                class="form-control"
                                placeholder="+1 555 123 4567"
                                inputmode="tel"
                                autocomplete="tel"
                                pattern="^\+?[0-9\s\-()]{7,}$"
                                title="Please enter a valid phone number. Example: +1 555 123 4567"
                                required
                        >
                    </div>


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
                    <div class="col-12 col-md-6">
                        <label for="password" class="form-label">Password</label>
                        <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                placeholder="Min. 8 characters"
                                minlength="8"
                                autocomplete="new-password"
                                required>
                    </div>

                    <!-- Repetir Password -->
                    <div class="col-12 col-md-6">
                        <label for="password2" class="form-label">Repeat Password</label>
                        <input
                                type="password"
                                id="password2"
                                name="password2"
                                class="form-control"
                                minlength="8"
                                autocomplete="new-password"
                                required>
                    </div>

                    <!--  Falta: validar en JS que coincidan 'password' y 'password2' antes de enviar, y vuelve a validar en servidor (siempre). -->

                    <!-- Aceptación de términos -->
                    <div class="col-12">
                        <div class="form-check">
                            <input
                                    class="form-check-input"
                                    type="checkbox"
                                    value="1"
                                    id="terms"
                                    name="terms"
                            required>
                            <label class="form-check-label" for="terms">
                                I accept the <a href="terms.php" target="_blank" rel="noopener">terms and conditions</a>.
                            </label>
                        </div>
                    </div>
                </div>

                <!-- CTA principal -->
                <button type="submit" class="btn btn-primary w-100 mt-4">Create Account</button>

                <!-- Enlace para cambiar a login sin recargar (previsiblemente lo gestiona js/auth.js) -->
                <div class="text-center mt-3">
                    <a
                            href="login.php"
                            class="auth-switch auth-link"
                    data-target="login.php">
                    I already have an account
                    </a>
                </div>

            </form>

            <!-- Pie de ayuda: contacto en caso de problemas -->
            <div class="auth-meta mt-4 text-center">
                <small class="text-muted">
                    Having trouble registering? <a href="contact.php">Contact us</a>
                </small>
            </div>
        </div>
    </section>
</main>

<?php include 'inc/footer.inc'; ?> <!-- Inserta el footer común del sitio -->
</body>
</html>
