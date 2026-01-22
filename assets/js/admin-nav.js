document.addEventListener('DOMContentLoaded', () => {
    const toggles = document.querySelectorAll('.admin-nav-toggle');

    toggles.forEach(toggle => {
        const targetSelector = toggle.getAttribute('data-nav-target');
        if (!targetSelector) return;
        const target = document.querySelector(targetSelector);
        if (!target) return;

        const openMenu = () => {
            target.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
        };

        const closeMenu = () => {
            target.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        };

        toggle.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();
            const isOpen = target.classList.contains('is-open');
            if (isOpen) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        document.addEventListener('click', event => {
            if (!target.classList.contains('is-open')) return;
            if (toggle.contains(event.target) || target.contains(event.target)) return;
            closeMenu();
        });

        target.querySelectorAll('a, button').forEach(el => {
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
