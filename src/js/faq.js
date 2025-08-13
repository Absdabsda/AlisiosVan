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

    // Acordeón por columna — solo en clics del usuario, y solo si NO hay filtros/búsqueda ni modo “bulk”
    cols.forEach(col => {
        qsa("details.faq-item", col).forEach(d => {
            d.addEventListener("toggle", (e) => {
                const userClick = e.isTrusted === true; // ← evento disparado por el usuario
                if (!userClick) return;                 // ignorar cambios programáticos
                if (bulk) return;                       // ignorar durante abrir/cerrar todo
                if (query) return;                      // con búsqueda activa no cerramos hermanos
                if (activeTags.size > 0) return;        // con chips activos tampoco

                // Solo cuando se ABRE, cerramos hermanos de su columna
                if (!e.target.open) return;
                qsa("details.faq-item", col).forEach(other => {
                    if (other !== e.target) other.open = false;
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

    // Abrir / cerrar todo — desactiva el cierre de hermanos mientras dura
    btnOpen?.addEventListener("click", () => {
        bulk = true;
        items.forEach(i => i.open = true);
        bulk = false;
    });
    btnClose?.addEventListener("click", () => {
        bulk = true;
        items.forEach(i => i.open = false);
        bulk = false;
    });

    // Filtrado principal
    function filter() {
        let visible = 0;

        // reset highlights
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
