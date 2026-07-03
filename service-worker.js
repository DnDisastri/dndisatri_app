const CACHE_NAME = 'dndisastri-v32';
const STATIC_ASSETS = [
  '/',
  '/index.html',
  '/style.css',
  '/app.js',
  '/manifest.json',
  '/logo.jpg'
];

// INSTALL
self.addEventListener('install', event => {
  console.log('[SW] Install');
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS))
  );
});

// ACTIVATE
self.addEventListener('activate', event => {
  console.log('[SW] Activate');
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys.map(key => {
          if (key !== CACHE_NAME) return caches.delete(key);
        })
      )
    )
  );
  self.clients.claim();
});

// FETCH
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);
  
  // Non cachare richieste non-HTTP
  if (!event.request.url.startsWith('http')) return;
  
  // Non cachare richieste POST, PUT, DELETE (solo GET)
  if (event.request.method !== 'GET') {
    console.log('[SW] Ignoring non-GET request:', event.request.method, url.pathname);
    return;
  }
  
  // Non cachare richieste a Firebase/API esterne
  if (url.hostname.includes('firebaseio.com') || 
      url.hostname.includes('googleapis.com') ||
      url.hostname.includes('gstatic.com') ||
      url.hostname.includes('firestore.googleapis.com') ||
      url.hostname.includes('cloudflare.com')) {
    console.log('[SW] Ignoring external API:', url.hostname);
    return;
  }

  // HTML - network first
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          const copy = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(event.request, copy));
          return response;
        })
        .catch(() => caches.match(event.request))
    );
    return;
  }

  // Asset - cache first
  event.respondWith(
    caches.match(event.request).then(cached => {
      if (cached) {
        console.log('[SW] Cache hit:', url.pathname);
        return cached;
      }
      
      console.log('[SW] Cache miss, fetching:', url.pathname);
      return fetch(event.request).then(response => {
        // Solo cachare risposte valide
        if (response && response.status === 200 && response.type === 'basic') {
          const copy = response.clone();
          caches.open(CACHE_NAME).then(cache =>
            cache.put(event.request, copy)
          );
        }
        return response;
      });
    })
  );
});

// LISTEN FOR UPDATE REQUEST
self.addEventListener('message', event => {
  if (event.data === 'SKIP_WAITING') {
    console.log('[SW] Skip waiting requested');
    self.skipWaiting();
  }
});