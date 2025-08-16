<?php
session_start();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Contact | Alisios Van</title>

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
    <link rel="stylesheet" href="css/contacto.css">
    <link rel="stylesheet" href="css/cookies.css">

    <script src="js/contacto.js" defer></script>
    <script src="js/cookies.js" defer></script>

</head>
<!-- Mini Chat WhatsApp – Alisios Van -->
<div id="wa-widget" aria-live="polite">
    <!-- Lanzador flotante -->
    <button id="wa-launcher" aria-label="Abrir chat de WhatsApp" title="WhatsApp">
        <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
    </button>

    <!-- Ventana del chat -->
    <div id="wa-panel" hidden>
        <div class="wa-header">
            <div class="wa-identity">
                <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
                <div>
                    <strong>Alisios Van</strong>
                    <div class="wa-status">WhatsApp</div>
                </div>
            </div>
            <button id="wa-close" aria-label="Cerrar chat" title="Cerrar">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>

        <div class="wa-messages" id="wa-messages"></div>

        <div class="wa-quick" id="wa-quick" aria-label="Opciones rápidas">
            <button type="button" data-text="Hola, me gustaría consultar disponibilidad.">Disponibilidad</button>
            <button type="button" data-text="¿Podríais enviarme precios y condiciones?">Precios</button>
            <button type="button" data-text="Tengo otra consulta.">Otro</button>
        </div>

        <div class="wa-input">
            <input type="text" id="wa-input" placeholder="Escribe y abre WhatsApp…" />
            <button id="wa-send" aria-label="Abrir WhatsApp">
                <i class="bi bi-send-fill" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</div>

<body>
<?php include 'inc/header.inc'; ?>

<main>
    <!-- HERO -->
    <section class="page-hero contact-hero pos-center">
        <div class="page-hero__content">
            <h1 class="page-hero__title">Contact</h1>
        </div>
    </section>

    <!-- BLOQUE CONTACTO -->
    <section class="contact-block">
        <div class="container">
            <div class="contact-grid">
                <!-- Columna izquierda: info -->
                <aside class="contact-card contact-info">
                    <h2 class="custom-title">Let’s plan your trip</h2>
                    <p>Tell us your dates and the van you’d like. We’ll get back to you quickly with availability and a simple quote.</p>

                    <ul class="contact-ways">
                        <li><i class="bi bi-envelope"></i> <a href="mailto:hello@alisiosvan.com">alisios.van@gmail.com</a></li>
                        <li><i class="bi bi-telephone"></i> <a href="tel:+34610136383">+34 610136383</a> <span class="muted">(WhatsApp)</span></li>
                        <li><i class="bi bi-geo-alt"></i> Puerto del Rosario, Fuerteventura</li>
                    </ul>

                    <div class="mini-note">
                        Prefer text? DM us on Instagram: <a href="https://instagram.com/alisios_van" target="_blank" rel="noopener"> @alisios_van</a>
                    </div>
                </aside>

                <!-- Columna derecha: formulario -->
                <section class="contact-card">
                    <form id="contactForm" action="../api/contact.php" method="post" novalidate>

                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                        <input type="text" name="website" tabindex="-1" autocomplete="off"
                               style="position:absolute;left:-9999px;opacity:0" aria-hidden="true">

                        <div class="form-row">
                            <div class="field">
                                <label for="name">Name*</label>
                                <input type="text" id="name" name="name" required />
                                <div class="invalid">Please enter your name.</div>
                            </div>

                            <div class="field">
                                <label for="email">Email*</label>
                                <input type="email" id="email" name="email" required />
                                <div class="invalid">Please enter a valid email.</div>
                            </div>

                            <div class="field">
                                <label for="phone">Phone (optional)</label>
                                <input type="tel" id="phone" name="phone" />
                            </div>

                            <div class="field">
                                <label for="model">Preferred model</label>
                                <select id="model" name="model">
                                    <option value="">Any</option>
                                    <option value="T3 Matcha">VW T3 “Matcha”</option>
                                    <option value="T3 Skye">VW T3 “Skye”</option>
                                    <option value="T4 Rusty">VW T4 “Rusty”</option>
                                </select>
                            </div>

                            <div class="field">
                                <label for="start">From</label>
                                <input type="date" id="start" name="start" />
                            </div>

                            <div class="field">
                                <label for="end">To</label>
                                <input type="date" id="end" name="end" />
                            </div>

                            <div class="field field--full">
                                <label for="message">Message*</label>
                                <textarea id="message" name="message" rows="5" required placeholder="Tell us a bit about your plan…"></textarea>
                                <div class="invalid">Please write a short message.</div>
                            </div>

                            <div class="field field--full checkbox">
                                <label>
                                    <input type="checkbox" id="privacy" name="privacy" required />
                                    I agree to the privacy policy.
                                </label>
                                <div class="invalid">Please accept to continue.</div>
                            </div>

                            <div class="field field--full">
                                <button type="submit" class="btn">Send request</button>
                            </div>
                        </div>

                        <!-- Mensaje de éxito -->
                        <p class="form-success" hidden>Thanks! We’ve received your message and will reply soon.</p>
                    </form>
                </section>
            </div>
        </div>
    </section>

    <!-- MAPA -->
    <section class="map-section">
        <div class="container">
            <h2 class="section-title text-center">Find us</h2>
            <div class="map-container">
                <iframe
                    src="https://www.google.com/maps?q=Puerto+del+Rosario,+Fuerteventura&hl=es&z=13&output=embed"
                    width="100%" height="420" style="border:0;" allowfullscreen="" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </section>
</main>
<?php include 'inc/footer.inc'; ?>
</body>
</html>
