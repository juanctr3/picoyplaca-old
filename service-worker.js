const CACHE_NAME = 'pico-placa-v2';
const urlsToCache = [
  '/',
  '/index.php',
  '/manifest.json'
];

// Instalar el service worker
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(urlsToCache).catch(err => {
        console.log('Error al cachear URLs:', err);
      });
    })
  );
  self.skipWaiting();
});

// Activar el service worker
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Interceptar requests
self.addEventListener('fetch', event => {
  // Solo GET
  if (event.request.method !== 'GET') {
    return;
  }

  event.respondWith(
    caches.match(event.request).then(response => {
      // Retornar del caché si existe
      if (response) {
        return response;
      }

      // Si no, hacer request a la red
      return fetch(event.request).then(response => {
        // No cachear si no es una response válida
        if (!response || response.status !== 200 || response.type === 'error') {
          return response;
        }

        // Clonar la response
        const responseToCache = response.clone();

        // Cachear para requests futuras
        caches.open(CACHE_NAME).then(cache => {
          cache.put(event.request, responseToCache);
        });

        return response;
      }).catch(err => {
        // Offline - retornar del caché o página offline
        console.log('Offline:', err);
        return caches.match(event.request);
      });
    })
  );
});

// Sincronización en background
self.addEventListener('sync', event => {
  if (event.tag === 'sync-pico-placa') {
    event.waitUntil(syncData());
  }
});

function syncData() {
  return fetch('/api/sync').then(response => {
    return caches.open(CACHE_NAME).then(cache => {
      return cache.addAll([
        fetch('/').then(response => response)
      ]);
    });
  });
}

// Push notifications
self.addEventListener('push', event => {
  const data = event.data?.json?.() ?? {};
  const title = data.title || 'Pico y Placa';
  const options = {
    body: data.body || 'Consulta las restricciones vehiculares',
    icon: 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 192"><rect fill="%23667eea" width="192" height="192"/><text x="50%" y="50%" font-size="120" font-weight="bold" text-anchor="middle" dy=".3em" fill="white" font-family="Arial">🚗</text></svg>',
    badge: 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96"><rect fill="%23667eea" width="96" height="96"/></svg>',
    tag: 'pico-placa',
    requireInteraction: false
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

// Notification click
self.addEventListener('notificationclick', event => {
  event.notification.close();
  event.waitUntil(
    clients.matchAll({ type: 'window' }).then(clientList => {
      for (let i = 0; i < clientList.length; i++) {
        if (clientList[i].url === '/' && 'focus' in clientList[i]) {
          return clientList[i].focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow('/');
      }
    })
  );
});