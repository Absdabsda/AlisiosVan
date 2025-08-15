// src/js/faq.js

// Utilidades
const normalize = (s) =>
    (s || "")
        .toString()
        .toLowerCase()
        .normalize("NFD")
        .replace(/\p{Diacritic}/gu, "");

const qs  = (sel, ctx=document) => ctx.querySelector(sel);
const qsa = (sel, ctx=document) => Array.from(ctx.querySelectorAll(sel));

document.addEventListener("DOMContentLoaded", () => {
    const input    = qs("#faqSearch");
    const clearBtn = qs("#faqClear");
    const countEl  = qs("#faqCount");
    const grid     = qs("#faqGrid");
    const cols     = qsa(".faq-col", grid);
    const items    = qsa("details.faq-item", grid);
    const emptyMsg = qs("#faqEmpty");
    const btnOpen  = qs("#expandAll");
    const btnClose = qs("#collapseAll");
    const chips    = qsa(".chip");

    // Estado
    let query = "";
    const activeTags = new Set(); // OR entre etiquetas
    let bulk = false;             // ← estamos haciendo abrir/cerrar “en bloque”

    // Helpers
    const visibleItems = () => items.filter(it => it.offsetParent !== null);

    // Lee parámetros ?q= & ?tags=
    const params    = new URLSearchParams(location.search);
    const qParam    = params.get("q");
    const tagsParam = params.get("tags");

    if (qParam) { input.value = qParam; query = normalize(qParam); }
    if (tagsParam) tagsParam.split(",").forEach(t => activeTags.add(t.trim()));

    // Sincroniza chips
    const allChip = chips.find?.(c => (c.dataset.tag || "") === "") || chips[0];
    chips.forEach(ch => {
        const tag = ch.dataset.tag || "";
        if (activeTags.size === 0) (tag === "" ? ch.classList.add("active") : ch.classList.remove("active"));
        else (activeTags.has(tag) ? ch.classList.add("active") : ch.classList.remove("active"));
    });

    // Acordeón por columna — solo en clics reales del usuario
    cols.forEach(col => {
        qsa("details.faq-item", col).forEach(d => {
            // Marcamos que el usuario hizo click en el summary (capturing para ir antes del toggle)
            const sum = d.querySelector("summary");
            sum?.addEventListener("click", () => { d.dataset.fromClick = "1"; }, true);

            d.addEventListener("toggle", () => {
                // Si estamos en modo "bulk" o el cambio no viene de click real, no aplicar acordeón
                if (bulk) return;

                const fromClick = d.dataset.fromClick === "1";
                d.dataset.fromClick = ""; // limpiar marca

                // Solo colapsar hermanos cuando el usuario abre un item (sin filtros/búsqueda)
                if (!fromClick) return;
                if (query) return;
                if (activeTags.size > 0) return;
                if (!d.open) return;

                qsa("details.faq-item", col).forEach(other => {
                    if (other !== d) other.open = false;
                });
            });
        });
    });

    // Búsqueda en vivo
    input?.addEventListener("input", () => {
        query = normalize(input.value.trim());
        filter();
    });

    // Limpiar
    clearBtn?.addEventListener("click", () => {
        input.value = "";
        query = "";
        filter();
        input.focus();
    });

    // Chips (toggle) — “All” limpia resto
    chips.forEach(chip => {
        chip.addEventListener("click", () => {
            const tag = chip.dataset.tag || "";
            if (tag === "") {
                activeTags.clear();
                chips.forEach(c => c.classList.remove("active"));
                chip.classList.add("active");
            } else {
                const was = chip.classList.contains("active");
                chip.classList.toggle("active");
                if (was) activeTags.delete(tag);
                else activeTags.add(tag);

                if (activeTags.size > 0) allChip?.classList.remove("active");
                else allChip?.classList.add("active");
            }
            filter();
        });
    });

    // Abrir / cerrar todo — solo sobre los visibles y desactiva acordeón mientras dura
    btnOpen?.addEventListener("click", () => {
        bulk = true;
        visibleItems().forEach(i => { i.open = true; });
        bulk = false;
    });

    btnClose?.addEventListener("click", () => {
        bulk = true;
        visibleItems().forEach(i => { i.open = false; });
        bulk = false;
    });

    // Filtrado principal
    function filter() {
        let visible = 0;

        // Reset highlights
        items.forEach(it => {
            const s = it.querySelector("summary");
            if (s) s.innerHTML = s.textContent;
        });

        items.forEach(it => {
            const text = normalize(it.textContent);
            const tags = (it.dataset.tags || "")
                .split(",").map(t => t.trim()).filter(Boolean);

            const matchesText = !query || text.includes(query);
            const matchesTags = activeTags.size === 0 || tags.some(t => activeTags.has(t));
            const ok = matchesText && matchesTags;

            it.style.display = ok ? "" : "none";
            if (ok) {
                visible++;
                if (query) {
                    it.open = true; // con búsqueda abrimos coincidencias
                    const sum = it.querySelector("summary");
                    if (sum) highlight(sum, query);
                }
            } else {
                it.open = false;
            }
        });

        emptyMsg?.classList.toggle("d-none", visible > 0);
        countEl.textContent = (query || activeTags.size)
            ? `Showing ${visible} result${visible === 1 ? "" : "s"}`
            : "Showing all";

        // Actualiza URL sin recargar
        const usp = new URLSearchParams();
        if (query) usp.set("q", input.value.trim());
        if (activeTags.size) usp.set("tags", Array.from(activeTags).join(","));
        const newUrl = `${location.pathname}${usp.toString() ? "?" + usp.toString() : ""}`;
        history.replaceState(null, "", newUrl);
    }

    function highlight(el, raw) {
        const q = raw.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
        const re = new RegExp(`(${q})`, "ig");
        el.innerHTML = el.textContent.replace(re, "<mark>$1</mark>");
    }

    // Primera pasada
    filter();

    // Si llega con hash (#faq-xxx) → abrir y hacer scroll
    if (location.hash) {
        const target = qs(location.hash);
        if (target && target.classList.contains("faq-item")) {
            target.open = true;
            target.scrollIntoView({ behavior: "smooth", block: "start" });
        }
    }
});
