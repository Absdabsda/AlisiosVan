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
    <link rel="stylesheet" href="/src/css/contacto.css">
    <link rel="stylesheet" href="/src/css/cookies.css">
    <script src="/src/js/header.js" defer></script>

    <script src="/src/js/contacto.js" defer></script>
    <script src="/src/js/cookies.js" defer></script>

    <style>
        :root { --header-bg-rgb: 84,70,62; } /* #54463E */
    </style>
</head>

<body>
<?php include 'inc/header.inc'; ?>

<!-- Mini Chat WhatsApp – Alisios Van -->
<div id="wa-widget" aria-live="polite">
    <!-- Lanzador flotante -->
    <button id="wa-launcher" aria-label="<?= __('Open WhatsApp chat') ?>" title="WhatsApp">
        <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
    </button>

    <!-- Ventana del chat -->
    <div id="wa-panel" hidden>
        <div class="wa-header">
            <div class="wa-identity">
                <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
                <div>
                    <strong>Alisios Van</strong>
                    <div class="wa-status"><?= __('WhatsApp') ?></div>
                </div>
            </div>
            <button id="wa-close" aria-label="<?= __('Close chat') ?>" title="<?= __('Close') ?>">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>

        <div class="wa-messages" id="wa-messages"></div>

        <div class="wa-quick" id="wa-quick" aria-label="<?= __('Quick options') ?>">
            <button type="button" data-text="<?= htmlspecialchars(__('Hello, I’d like to check availability.')) ?>">
                <?= __('Availability') ?>
            </button>
            <button type="button" data-text="<?= htmlspecialchars(__('Could you send me prices and terms?')) ?>">
                <?= __('Prices') ?>
            </button>
            <button type="button" data-text="<?= htmlspecialchars(__('I have another question.')) ?>">
                <?= __('Other') ?>
            </button>
        </div>

        <div class="wa-input">
            <input type="text" id="wa-input" placeholder="<?= __('Type and open WhatsApp…') ?>" />
            <button id="wa-send" aria-label="<?= __('Open WhatsApp') ?>">
                <i class="bi bi-send-fill" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</div>

<main>
    <!-- HERO -->
    <section class="page-hero contact-hero pos-center">
        <div class="page-hero__content">
            <h1 class="page-hero__title"><?= __('Contact') ?></h1>
        </div>
    </section>

    <!-- BLOQUE CONTACTO -->
    <section class="contact-block">
        <div class="container">
            <div class="contact-grid">
                <!-- Columna izquierda: info -->
                <aside class="contact-card contact-info">
                    <h2 class="custom-title"><?= __('Let’s plan your trip') ?></h2>
                    <p><?= __('Tell us your dates and the van you’d like. We’ll get back to you quickly with availability and a simple quote.') ?></p>

                    <ul class="contact-ways">
                        <li><i class="bi bi-envelope"></i> <a href="mailto:alisios.van@gmail.com">alisios.van@gmail.com</a></li>
                        <li><i class="bi bi-telephone"></i> <a href="tel:+34610136383">+34 610136383</a> <span class="muted">(WhatsApp)</span></li>
                        <li><i class="bi bi-geo-alt"></i> <?= __('Puerto del Rosario, Fuerteventura') ?></li>
                    </ul>

                    <div class="mini-note">
                        <?= __('Prefer text? DM us on Instagram:') ?>
                        <a href="https://instagram.com/alisios_van" target="_blank" rel="noopener"> @alisios_van</a>
                    </div>
                </aside>

                <!-- Columna derecha: formulario -->
                <section class="contact-card">
                    <form id="contactForm" action="../api/contact.php" method="post" novalidate>
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
                        <input type="text" name="website" tabindex="-1" autocomplete="off" class="hp-field" aria-hidden="true">

                        <div class="form-row">
                            <div class="field">
                                <label for="name"><?= __('Name*') ?></label>
                                <input type="text" id="name" name="name" required />
                                <div class="invalid"><?= __('Please enter your name.') ?></div>
                            </div>

                            <div class="field">
                                <label for="email"><?= __('Email*') ?></label>
                                <input type="email" id="email" name="email" required />
                                <div class="invalid"><?= __('Please enter a valid email.') ?></div>
                            </div>

                            <div class="field">
                                <label for="phone"><?= __('Phone (optional)') ?></label>
                                <input type="tel" id="phone" name="phone" />
                            </div>

                            <div class="field">
                                <label for="model"><?= __('Preferred model') ?></label>
                                <select id="model" name="model">
                                    <option value=""><?= __('Any') ?></option>
                                    <option value="T3 Matcha">VW T3 “Matcha”</option>
                                    <option value="T3 Skye">VW T3 “Skye”</option>
                                    <option value="T4 Rusty">VW T4 “Rusty”</option>
                                </select>
                            </div>

                            <div class="field">
                                <label for="start"><?= __('From') ?></label>
                                <input type="date" id="start" name="start" />
                            </div>

                            <div class="field">
                                <label for="end"><?= __('To') ?></label>
                                <input type="date" id="end" name="end" />
                            </div>

                            <div class="field field--full">
                                <label for="message"><?= __('Message*') ?></label>
                                <textarea id="message" name="message" rows="5" required placeholder="<?= __('Tell us a bit about your plan…') ?>"></textarea>
                                <div class="invalid"><?= __('Please write a short message.') ?></div>
                            </div>

                            <div class="field field--full checkbox">
                                <label>
                                    <input type="checkbox" id="privacy" name="privacy" required />
                                    <?= __('I agree to the privacy policy.') ?>
                                </label>
                                <div class="invalid"><?= __('Please accept to continue.') ?></div>
                            </div>

                            <div class="field field--full">
                                <button type="submit" class="btn btn-primary"><?= __('Send request') ?></button>
                            </div>
                        </div>

                        <!-- Mensaje de éxito -->
                        <p class="form-success" hidden><?= __('Thanks! We’ve received your message and will reply soon.') ?></p>
                    </form>
                </section>
            </div>
        </div>
    </section>

    <!-- MAPA -->
    <?php
    // idioma del mapa de Google
    $hl = in_array(($LANG ?? 'en'), ['es','en','de','fr','it'], true) ? $LANG : 'en';
    ?>
    <section class="map-section">
        <div class="container">
            <h2 class="section-title text-center"><?= __('Find us') ?></h2>
            <div class="map-container">
                <iframe
                        src="https://www.google.com/maps?q=Puerto+del+Rosario,+Fuerteventura&hl=<?= htmlspecialchars($hl) ?>&z=13&output=embed"
                        width="100%" height="420" style="border:0;" allowfullscreen="" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </section>
</main>

<?php include 'inc/footer.inc'; ?>
</body>

</html>
