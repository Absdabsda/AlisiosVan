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

    <script src="js/contacto.js" defer></script>
</head>
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

<!-- WhatsApp Chat – Alisios Van -->
<a id="wa-chat"
   href="https://wa.me/34610136383"
   target="_blank"
   rel="nofollow noopener"
   aria-label="Chat on WhatsApp"
   style="position:fixed; right:16px; bottom:16px; z-index:1050; display:flex; align-items:center; gap:.5rem; background:#25D366; color:#fff; padding:12px 14px; border-radius:24px; text-decoration:none; box-shadow:0 2px 10px rgba(0,0,0,.2); font-family:Quicksand,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; font-weight:700;">
    <i class="bi bi-whatsapp" aria-hidden="true" style="font-size:1.25rem;"></i>
    <span class="wa-text">WhatsApp</span>
</a>

<script>
    (function () {
        // 👉 Ajusta tu número aquí (sin +): 34 + número
        var phone = '34610136383';

        // 👉 Mensaje inicial (puedes editarlo)
        var msg = "Hi! I'd like info about a camper rental. I'm contacting you from the Contact page:";

        // Construye el enlace con texto + URL de la página
        var url = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(msg + ' ' + window.location.href);

        var a = document.getElementById('wa-chat');
        a.href = url;

        // (Opcional) Mide clics si tienes Analytics
        if (typeof gtag === 'function') {
            a.addEventListener('click', function () {
                gtag('event', 'click', { event_category: 'engagement', event_label: 'whatsapp_contact_page' });
            });
        } else if (window.dataLayer) {
            a.addEventListener('click', function () {
                window.dataLayer.push({ event: 'whatsapp_click', location: 'contact' });
            });
        }
    })();
</script>

<style>
    /* Oculta el texto en móvil: solo icono */
    @media (max-width: 575.98px) {
        #wa-chat .wa-text { display: none; }
    }
    /* Pequeña animación al pasar el ratón (respeta reduced motion por defecto) */
    #wa-chat:hover { filter: brightness(1.05); transform: translateY(-1px); }
</style>


<?php include 'inc/footer.inc'; ?>
</body>
</html>
