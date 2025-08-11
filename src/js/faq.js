// src/js/faq.js
document.querySelectorAll('.faq-question').forEach(button => {
    button.addEventListener('click', () => {
        const answer = button.nextElementSibling;
        const isOpen = answer.style.maxHeight;

        // Cierra todos
        document.querySelectorAll('.faq-answer').forEach(a => a.style.maxHeight = null);

        // Abre si estaba cerrado
        if (!isOpen) {
            answer.style.maxHeight = answer.scrollHeight + "px";
        }
    });
});


