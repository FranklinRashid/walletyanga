document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-mobile-nav]');

    if (!root) {
        return;
    }

    const openButton = root.querySelector('[data-mobile-nav-open]');
    const closeButton = root.querySelector('[data-mobile-nav-close]');
    const overlay = root.querySelector('[data-mobile-nav-overlay]');
    const drawer = root.querySelector('[data-mobile-nav-drawer]');

    const open = () => {
        overlay.classList.remove('hidden');
        requestAnimationFrame(() => {
            overlay.classList.remove('opacity-0');
            drawer.classList.remove('-translate-x-full');
            openButton?.setAttribute('aria-expanded', 'true');
            document.body.classList.add('overflow-hidden');
        });
    };

    const close = () => {
        overlay.classList.add('opacity-0');
        drawer.classList.add('-translate-x-full');
        openButton?.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('overflow-hidden');

        window.setTimeout(() => {
            overlay.classList.add('hidden');
        }, 200);
    };

    openButton?.addEventListener('click', open);
    closeButton?.addEventListener('click', close);
    overlay?.addEventListener('click', close);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
        }
    });
});
