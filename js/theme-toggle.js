(function () {
    const STORAGE_KEY = 'anime_storage_theme';
    const DARK = 'theme-dark';
    const LIGHT = 'theme-light';

    function applyTheme(theme) {
        document.body.classList.remove(DARK, LIGHT);
        document.body.classList.add(theme);
        updateToggleLabels(theme);
    }

    function updateToggleLabels(theme) {
        const toggles = document.querySelectorAll('[data-theme-toggle]');
        const isLight = theme === LIGHT;
        toggles.forEach(toggle => {
            toggle.textContent = isLight ? '☀️' : '🌙';
            toggle.setAttribute('aria-pressed', String(isLight));
            toggle.setAttribute('title', isLight ? 'Mode gelap' : 'Mode terang');
        });
    }

    function closeMenu(nav) {
        if (!nav || !nav.classList.contains('is-open')) {
            return;
        }
        nav.classList.remove('is-open');
        const toggle = nav.querySelector('[data-menu-toggle]');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    }

    function init() {
        const saved = localStorage.getItem(STORAGE_KEY);
        const prefersLight = window.matchMedia('(prefers-color-scheme: light)').matches;
        const initialTheme = saved === LIGHT || (saved === null && prefersLight) ? LIGHT : DARK;

        applyTheme(initialTheme);

        document.addEventListener('click', (event) => {
            const menuToggle = event.target.closest('[data-menu-toggle]');
            if (menuToggle) {
                const nav = menuToggle.closest('[data-nav]');
                if (nav) {
                    const nextState = !nav.classList.contains('is-open');
                    nav.classList.toggle('is-open', nextState);
                    menuToggle.setAttribute('aria-expanded', String(nextState));
                    if (!nextState) {
                        menuToggle.blur();
                    }
                }
                return;
            }

            const navLink = event.target.closest('[data-nav-links] a');
            if (navLink) {
                closeMenu(navLink.closest('[data-nav]'));
            } else if (!event.target.closest('[data-nav]')) {
                document.querySelectorAll('[data-nav].is-open').forEach(closeMenu);
            }

            const toggle = event.target.closest('[data-theme-toggle]');
            if (toggle) {
                const current = document.body.classList.contains(LIGHT) ? LIGHT : DARK;
                const next = current === LIGHT ? DARK : LIGHT;
                applyTheme(next);
                localStorage.setItem(STORAGE_KEY, next);
            }
        });

        const mediaQuery = window.matchMedia('(prefers-color-scheme: light)');
        mediaQuery.addEventListener('change', (event) => {
            const savedPref = localStorage.getItem(STORAGE_KEY);
            if (savedPref) {
                return;
            }
            applyTheme(event.matches ? LIGHT : DARK);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
