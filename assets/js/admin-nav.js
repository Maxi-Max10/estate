document.addEventListener('DOMContentLoaded', () => {
    const toggles = document.querySelectorAll('.admin-nav-toggle');

    toggles.forEach(toggle => {
        const targetSelector = toggle.getAttribute('data-nav-target');
        if (!targetSelector) return;
        const target = document.querySelector(targetSelector);
        if (!target) return;

        const closeMenu = () => {
            if (!target.classList.contains('show')) return;
            target.classList.remove('show');
            toggle.setAttribute('aria-expanded', 'false');
        };

        toggle.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();
            const isOpen = target.classList.contains('show');
            target.classList.toggle('show', !isOpen);
            toggle.setAttribute('aria-expanded', String(!isOpen));
        });

        document.addEventListener('click', event => {
            if (!target.classList.contains('show')) return;
            if (toggle.contains(event.target) || target.contains(event.target)) return;
            closeMenu();
        });

        target.querySelectorAll('a, button').forEach(el => {
            if (el === toggle) return;
            el.addEventListener('click', () => {
                closeMenu();
            });
        });

        document.addEventListener('keydown', event => {
            if (event.key !== 'Escape') return;
            closeMenu();
        });
    });
});
