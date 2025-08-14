/* Animación de salida antes de redirigir */
(function () {
    const split = document.querySelector('.auth-split');
    const switchBtns = document.querySelectorAll('.auth-switch');
    if (!split || !switchBtns.length) return;

    const DURATION = 700; // debe coincidir con CSS
    const context = split.getAttribute('data-auth-context'); // "register" | "login"

    switchBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            // Evita navegación inmediata si es <a>
            if (e && typeof e.preventDefault === 'function') e.preventDefault();

            // Destino: data-target > href (si existe) > fallback
            const target =
                btn.getAttribute('data-target') ||
                btn.getAttribute('href') ||
                'login.php';

            // Dirección según contexto actual:
            // - En register: salida "normal" (leaving)
            // - En login: salida "inversa" (leaving-reverse)
            if (context === 'login') {
                split.classList.add('leaving-reverse');
            } else {
                split.classList.add('leaving');
            }

            let redirected = false;
            const go = () => {
                if (redirected) return;
                redirected = true;
                window.location.href = target;
            };

            // Si tienes dos transiciones relevantes (media + panel), espera ambas
            let pending = 2;
            const onEnd = (ev) => {
                if (ev.propertyName !== 'transform') return;
                if (--pending <= 0) {
                    split.removeEventListener('transitionend', onEnd, true);
                    go();
                }
            };

            split.addEventListener('transitionend', onEnd, true);
            setTimeout(go, DURATION + 80); // fallback por si no dispara transitionend
        });
    });
})();

/* Validación de coincidencia de contraseñas (usa #password y #password2) */
(function () {
    const pwd = document.getElementById('password');
    const confirm = document.getElementById('password2');

    if (!pwd || !confirm) return;

    function validateMatch() {
        if (confirm.value !== pwd.value) {
            confirm.setCustomValidity("Passwords do not match");
        } else {
            confirm.setCustomValidity("");
        }
    }

    pwd.addEventListener('input', validateMatch);
    confirm.addEventListener('input', validateMatch);

    // Valida al cargar (por si hay autocompletado del navegador)
    validateMatch();
})();
