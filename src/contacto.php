<?php
declare(strict_types=1);
require __DIR__ . '/../config/i18n-lite.php'; // necesario para $LANG y __()

session_start();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// Idioma actual
$lang = strtolower($LANG ?? ($_GET['lang'] ?? 'es'));

// Canonical
$canonical = "https://alisiosvan.com/$lang/contacto/";

// Idiomas disponibles
$supportedLangs = ['es','en','de','fr','it'];

// Meta description por idioma
$descriptions = [
    'es' => 'Contacta con Alisios Van para consultar disponibilidad, precios o resolver dudas sobre nuestras campers en Fuerteventura.',
    'en' => 'Contact Alisios Van to check availability, prices or ask any questions about our camper rentals in Fuerteventura.',
    'de' => 'Kontaktieren Sie Alisios Van für Verfügbarkeit, Preise oder Fragen zur Camper-Vermietung auf Fuerteventura.',
    'fr' => 'Contactez Alisios Van pour vérifier la disponibilité, les tarifs ou poser vos questions sur nos vans à Fuerteventura.',
    'it' => 'Contatta Alisios Van per disponibilità, prezzi o domande sui nostri camper a Fuerteventura.',
];

$metaDescription = $descriptions[$lang] ?? $descriptions['en'];

// Hreflang
$hreflangs = "";
foreach ($supportedLangs as $l) {
    $hreflangs .= '<link rel="alternate" hreflang="'.$l.'" href="https://alisiosvan.com/'.$l.'/contacto/" />'."\n";
}
$hreflangs .= '<link rel="alternate" hreflang="x-default" href="https://alisiosvan.com/es/contacto/" />';
?>
<!doctype html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- SEO -->
    <title><?= __('Contact | Alisios Van') ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
    <?= $hreflangs ?>

    <!-- Open Graph -->
    <meta property="og:title" content="<?= __('Contact | Alisios Van') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:image" content="https://alisiosvan.com/src/img/contact-og.jpg">
    <meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
    <meta property="og:type" content="website">

    <!-- Twitter / X -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= __('Contact | Alisios Van') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="twitter:image" content="https://alisiosvan.com/src/img/contact-og.jpg">

    <!-- evita traducción automática -->
    <meta name="google" content="notranslate">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" defer></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flag-icons/css/flag-icons.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="/src/css/estilos.css">
    <link rel="stylesheet" href="/src/css/header.css">
    <link rel="stylesheet" href="/src/css/contacto.css">
    <link rel="stylesheet" href="/src/css/cookies.css">

    <!-- JS -->
    <script src="/src/js/header.js" defer></script>
    <script src="/src/js/contacto.js" defer></script>
    <script src="/src/js/cookies.js" defer></script>

    <style>
        :root { --header-bg-rgb: 84,70,62; }
    </style>
</head>

<body>
<?php include 'inc/header.inc'; ?>

<!-- WhatsApp Widget -->
<div id="wa-widget" aria-live="polite">
    <button id="wa-launcher" aria-label="<?= __('Open WhatsApp chat') ?>" title="WhatsApp">
        <i class="fa-brands fa-whatsapp"></i>
    </button>

    <div id="wa-panel" hidden>
        <div class="wa-header">
            <div class="wa-identity">
                <i class="fa-brands fa-whatsapp"></i>
                <div>
                    <strong>Alisios Van</strong>
                    <div class="wa-status"><?= __('WhatsApp') ?></div>
                </div>
            </div>
            <button id="wa-close" aria-label="<?= __('Close') ?>">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="wa-messages" id="wa-messages"></div>

        <div class="wa-quick">
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
            <input type="text" id="wa-input" placeholder="<?= __('Type and open WhatsApp…') ?>">
            <button id="wa-send" aria-label="<?= __('Open WhatsApp') ?>">
                <i class="bi bi-send-fill"></i>
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

    <!-- CONTACT BLOCK -->
    <section class="contact-block">
        <div class="container">
            <div class="contact-grid">

                <!-- LEFT COLUMN -->
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
                        <a href="https://instagram.com/alisios_van" target="_blank" rel="noopener">@alisios_van</a>
                    </div>
                </aside>

                <!-- RIGHT COLUMN — FORM -->
                <section class="contact-card">
                    <form id="contactForm" action="/api/contact.php" method="post" novalidate>
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                        <input type="text" name="website" tabindex="-1" class="hp-field" aria-hidden="true">

                        <div class="form-row">
                            <div class="field">
                                <label for="name"><?= __('Name*') ?></label>
                                <input type="text" id="name" name="name" required>
                                <div class="invalid"><?= __('Please enter your name.') ?></div>
                            </div>

                            <div class="field">
                                <label for="email"><?= __('Email*') ?></label>
                                <input type="email" id="email" name="email" required>
                                <div class="invalid"><?= __('Please enter a valid email.') ?></div>
                            </div>

                            <div class="field">
                                <label for="phone"><?= __('Phone (optional)') ?></label>
                                <input type="tel" id="phone" name="phone">
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
                                <input type="date" id="start" name="start">
                            </div>

                            <div class="field">
                                <label for="end"><?= __('To') ?></label>
                                <input type="date" id="end" name="end">
                            </div>

                            <div class="field field--full">
                                <label for="message"><?= __('Message*') ?></label>
                                <textarea id="message" name="message" rows="5" required placeholder="<?= __('Tell us a bit about your plan…') ?>"></textarea>
                                <div class="invalid"><?= __('Please write a short message.') ?></div>
                            </div>

                            <div class="field field--full checkbox">
                                <label>
                                    <input type="checkbox" id="privacy" name="privacy" required>
                                    <?= __('I agree to the privacy policy.') ?>
                                </label>
                                <div class="invalid"><?= __('Please accept to continue.') ?></div>
                            </div>

                            <div class="field field--full">
                                <button type="submit" class="btn btn-primary"><?= __('Send request') ?></button>
                            </div>
                        </div>

                        <p class="form-success" hidden><?= __('Thanks! We’ve received your message and will reply soon.') ?></p>
                    </form>
                </section>
            </div>
        </div>
    </section>

    <!-- MAP -->
    <?php
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
