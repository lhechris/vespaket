const CACHE_NAME = 'vespaket-v1.0';
const URLS_TO_CACHE = [
  '/',
  '/offline.html',
  '/manifest.json',
  '/images/icon-192.png',
  '/images/icon-512.png'
];

// Installation du service worker
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(URLS_TO_CACHE).catch(() => {
        // Les URLs peuvent ne pas être disponibles, c'est ok
        console.log('Certaines ressources n\'ont pas pu être cachées');
      });
    })
  );
  self.skipWaiting();
});

// Activation du service worker
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Gestion des requêtes (Network First avec fallback au cache)
self.addEventListener('fetch', (event) => {
  // Ne mettre en cache que les GET
  if (event.request.method !== 'GET') {
    return;
  }

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // Ne mettre en cache que les réponses valides
        if (!response || response.status !== 200 || response.type === 'error') {
          return response;
        }

        const url = new URL(event.request.url);
        if (url.origin !== self.location.origin) {
            return response; 
        }

        // Cloner la réponse
        const responseToCache = response.clone();

        // Vérifier si c'est une ressource statique (assets, CSS, JS, images)
        const requestUrl = new URL(event.request.url);
        if (
          requestUrl.pathname.startsWith('/build/') ||
          requestUrl.pathname.startsWith('/images/') ||
          requestUrl.pathname.startsWith('/fonts/') ||
          event.request.destination === 'style' ||
          event.request.destination === 'script' ||
          event.request.destination === 'image'
        ) {
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, responseToCache);
          });
        }

        return response;
      })
      .catch(() => {
        // Si la requête échoue, essayer le cache
        return caches.match(event.request).then((response) => {
          return response || new Response('Offline - Page not available', {
            status: 503,
            statusText: 'Service Unavailable',
            headers: new Headers({
              'Content-Type': 'text/plain'
            })
          });
        });
      })
  );
});

self.addEventListener('push', function(event) {
    const data = event.data?.json?.() ?? {};
    const url = data.data?.url ?? data.url ?? '/';

    event.waitUntil(
        self.registration.showNotification(data.title ?? 'ASLB', {
            body: data.body ?? '',
            icon: data.icon ?? '/images/logo.png',
            badge: data.badge ?? '/icons/icon-72.png',
            data: { url }
        })
    );
});

self.addEventListener('notificationclick', function(event) {
    event.preventDefault();
    event.notification.close();

    const url = event.notification?.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            const matchingClient = windowClients.find((client) => {
                return client.url.includes(self.location.origin) && 'focus' in client;
            });

            if (matchingClient) {
                return matchingClient.focus().then(() => matchingClient.navigate(url));
            }

            return clients.openWindow(url);
        })
    );
});
