<!-- FOOTER -->
<footer class="footer bg-dark pt-5 pb-4">
    <!-- Cookie Banner -->
    <div id="cc-banner" class="cc-banner" role="region" aria-label="Cookie notice" hidden>
        <div class="cc-text">
            We use cookies to run the site and to understand how it’s used. You can accept all,
            reject non-essential, or choose your preferences. <a href="cookies.php" target="_blank" rel="noopener">Cookie Policy</a>.
        </div>
        <div class="cc-actions">
            <button class="cc-btn cc-btn-outline" data-cc="reject">Reject all</button>
            <button class="cc-btn cc-btn-outline" data-cc="customize">Customize</button>
            <button class="cc-btn cc-btn-primary" data-cc="accept">Accept all</button>
        </div>
    </div>

    <!-- Cookie Settings Modal -->
    <div id="cc-modal" class="cc-modal" role="dialog" aria-modal="true" aria-labelledby="cc-modal-title" hidden>
        <div class="cc-dialog">
            <h3 id="cc-modal-title">Cookie settings</h3>
            <p class="cc-sub">Choose which cookies to allow. Essential cookies are always on.</p>

            <div class="cc-row">
                <label class="cc-switch">
                    <input type="checkbox" checked disabled>
                    <span>Essential (required)</span>
                </label>
                <small>Needed for security and basic site functions.</small>
            </div>

            <div class="cc-row">
                <label class="cc-switch">
                    <input type="checkbox" id="cc-pref">
                    <span>Preferences</span>
                </label>
                <small>Remember choices like language or region.</small>
            </div>

            <div class="cc-row">
                <label class="cc-switch">
                    <input type="checkbox" id="cc-analytics">
                    <span>Analytics</span>
                </label>
                <small>Help us improve the site with anonymous usage stats.</small>
            </div>

            <div class="cc-actions end">
                <button class="cc-btn cc-btn-outline" data-cc="modal-cancel">Cancel</button>
                <button class="cc-btn cc-btn-primary" data-cc="modal-save">Save choices</button>
            </div>
        </div>
    </div>

    <div class="container text-center text-md-left">
        <div class="row">
            <!-- Logo y Descripción -->
            <div class="col-md-4 mb-4">
                <a href="index.php" class="d-inline-flex align-items-center text-decoration-none mb-2">
                    <img
                            src="img/logo-blanco.png"
                    alt="Alisios Van"
                    class="footer-logo-img"
                    width="180" height="48"
                    loading="lazy" decoding="async">
                </a>
                <p class="mb-0">Freedom, adventure, and island vibes — one van at a time.</p>
            </div>


            <!-- Enlaces útiles -->
            <div class="col-md-4 mb-4">
                <h5 class="text-uppercase mb-4">Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="index.php" class="text-white text-decoration-none">Home</a></li>
                    <li><a href="campers.php" class="text-white text-decoration-none">Campers</a></li>
                    <li><a href="sobre-nosotros.php" class="text-white text-decoration-none">About Us</a></li>
                    <li><a href="faq.php" class="text-white text-decoration-none">FAQ</a></li>
                    <li><a href="contacto.php" class="text-white text-decoration-none">Contact</a></li>
                </ul>
            </div>

            <!-- Legal & Social -->
            <div class="col-md-4 mb-4">
                <h5 class="text-uppercase mb-4">Legal</h5>
                <ul class="list-unstyled mb-3">
                    <li><a href="terms.php" class="text-white text-decoration-none">Terms of Service</a></li>
                    <li><a href="legal.php" class="text-white text-decoration-none">Legal Notice</a></li>
                    <li><a href="privacy.php" class="text-white text-decoration-none">Privacy Policy</a></li>
                    <li><a href="cookies.php" class="text-white text-decoration-none">Cookie Policy</a></li>
                    <li><a href="#" class="text-white text-decoration-none" onclick="cookieConsent?.openSettings();return false;">Cookie settings</a></li>
                </ul>

                <!--Iconos redes sociales-->
                <div class="d-flex gap-3 footer-social">
                    <a href="https://www.instagram.com/alisios_van/" target="_blank" class="text-white" aria-label="Instagram">
                        <i class="fab fa-instagram fa-lg"></i>
                    </a>
                    <a href="#" class="text-white" aria-label="Facebook">
                        <i class="fab fa-facebook fa-lg"></i>
                    </a>
                    <a href="#" class="text-white" aria-label="TikTok">
                        <i class="fab fa-tiktok fa-lg"></i>
                    </a>
                </div>

            </div>
        </div>
    </div>

    <!-- Línea inferior -->
    <div class="text-center mt-3 border-top pt-3" style="font-size: 0.9rem;">
        &copy; 2025 Alisios Van. All rights reserved.
    </div>
</footer>

<!-- Asegúrate de cargar el JS del banner en todas las páginas -->
<script src="js/cookies.js" defer></script>
