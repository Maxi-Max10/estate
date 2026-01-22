document.addEventListener('DOMContentLoaded', () => {
    const toggler = document.querySelector('.navbar-toggler[data-bs-target="#adminNavLinks"]');
    const collapseEl = document.getElementById('adminNavLinks');

    if (!toggler || !collapseEl) {
        return;
    }

    let lastShowAt = 0;

    collapseEl.addEventListener('show.bs.collapse', () => {
        lastShowAt = Date.now();
    });

    collapseEl.addEventListener('hide.bs.collapse', event => {
        if (Date.now() - lastShowAt < 300) {
            event.preventDefault();
        }
    });
});
