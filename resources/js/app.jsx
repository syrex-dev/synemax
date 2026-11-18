import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'SynemaX';

function syncPwaHead(pathname) {
    if (typeof document === 'undefined') return;
    const path = pathname || window.location.pathname;
    const isAdmin = path.startsWith('/admin');

    const manifestEl = document.getElementById('app-manifest') || document.querySelector('link[rel="manifest"]');
    const targetManifest = isAdmin ? '/admin.webmanifest?v=7' : '/site.webmanifest?v=7';
    if (manifestEl && manifestEl.getAttribute('href') !== targetManifest) {
        const newManifestEl = document.createElement('link');
        newManifestEl.id = 'app-manifest';
        newManifestEl.rel = 'manifest';
        newManifestEl.href = targetManifest;
        manifestEl.parentNode.replaceChild(newManifestEl, manifestEl);
    }

    const appleIconEl = document.getElementById('app-apple-touch-icon') || document.querySelector('link[rel="apple-touch-icon"]');
    const targetAppleIcon = isAdmin ? '/apple-touch-icon-admin.png?v=7' : '/apple-touch-icon.png?v=7';
    if (appleIconEl && appleIconEl.getAttribute('href') !== targetAppleIcon) {
        appleIconEl.setAttribute('href', targetAppleIcon);
    }

    const mobileTitleMeta = document.getElementById('app-mobile-title') || document.querySelector('meta[name="apple-mobile-web-app-title"]');
    const targetTitle = isAdmin ? 'SynemaX Admin' : (appName || 'SynemaX');
    if (mobileTitleMeta && mobileTitleMeta.getAttribute('content') !== targetTitle) {
        mobileTitleMeta.setAttribute('content', targetTitle);
    }
}

if (typeof window !== 'undefined') {
    // Initial sync
    syncPwaHead(window.location.pathname);

    // Sync on client-side route changes
    router.on('navigate', () => {
        syncPwaHead(window.location.pathname);
    });

    // Capture beforeinstallprompt and store separately by active area
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        const isAdmin = window.location.pathname.startsWith('/admin');
        if (isAdmin) {
            window.__synemax_pwa_prompt_admin = e;
        } else {
            window.__synemax_pwa_prompt_public = e;
        }
        window.__synemax_pwa_prompt = e;
        window.dispatchEvent(new CustomEvent('synemax-pwa-prompt-ready', { detail: { isAdmin, prompt: e } }));
    });
}

createInertiaApp({
    title: (title) => {
        const site = appName || 'SynemaX';
        if (!title || title.trim() === '' || title === site) {
            return `${site} | Beranda`;
        }
        if (title.startsWith(`${site} |`)) {
            return title;
        }
        return `${site} | ${title}`;
    },
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });
        const page = pages[`./Pages/${name}.jsx`];
        if (!page) {
            throw new Error(`Inertia page component not found: ./Pages/${name}.jsx`);
        }
        return page;
    },
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(<App {...props} />);
    },
    progress: {
        color: '#D4AF37',
        showSpinner: false,
    },
});
