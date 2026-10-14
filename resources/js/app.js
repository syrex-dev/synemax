

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const theme = window.synemaXTheme;
const themeToggles = document.querySelectorAll('[data-theme-toggle]');

if (theme) {
    const syncThemeToggles = () => {
        const isDark = document.documentElement.classList.contains('dark');

        themeToggles.forEach((toggle) => {
            toggle.setAttribute('aria-checked', String(isDark));
        });
    };

    syncThemeToggles();

    themeToggles.forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const nextMode = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
            theme.setMode(nextMode);
            syncThemeToggles();
        });
    });

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (theme.getMode() === 'system') {
            theme.applyMode('system');
            syncThemeToggles();
        }
    });

    window.addEventListener('storage', (event) => {
        if (event.key === theme.storageKey) {
            const mode = theme.getMode();
            theme.applyMode(mode);
            syncThemeToggles();
        }
    });
}
