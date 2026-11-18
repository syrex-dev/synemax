const CACHE_NAME = 'synemax-v3';
const OFFLINE_URL = '/';

const PRECACHE_ASSETS = [
    '/',
    '/icon-192.png?v=3',
    '/icon-512.png?v=3',
    '/icon.svg?v=3',
    '/apple-touch-icon.png?v=3',
    '/icon-admin-192.png?v=4',
    '/icon-admin-512.png?v=4',
    '/icon-admin.svg?v=4',
    '/apple-touch-icon-admin.png?v=4',
    '/site.webmanifest?v=3',
    '/admin.webmanifest?v=4'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_ASSETS).catch(() => {});
        }).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((name) => {
                    if (name !== CACHE_NAME) {
                        return caches.delete(name);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Only handle GET requests
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // Bypass caching for admin mutations or API calls that need live database state
    if (url.pathname.startsWith('/admin') || url.pathname.includes('/live_search') || url.pathname.includes('/lookup')) {
        event.respondWith(
            fetch(request).catch(() => caches.match(request))
        );
        return;
    }

    // Static assets: cache-first with network fallback
    if (
        url.pathname.startsWith('/build/') ||
        url.pathname.endsWith('.woff2') ||
        url.pathname.endsWith('.png') ||
        url.pathname.endsWith('.jpg') ||
        url.pathname.endsWith('.svg') ||
        url.pathname.endsWith('.css') ||
        url.pathname.endsWith('.js')
    ) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseToCache);
                        });
                    }
                    return networkResponse;
                }).catch(() => cachedResponse);
            })
        );
        return;
    }

    // Navigation and HTML pages: Network-first with cache fallback
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).then((networkResponse) => {
                return networkResponse;
            }).catch(() => {
                return caches.match(request).then((cachedResponse) => {
                    return cachedResponse || caches.match(OFFLINE_URL);
                });
            })
        );
        return;
    }

    // Default network-first
    event.respondWith(
        fetch(request).catch(() => caches.match(request))
    );
});
