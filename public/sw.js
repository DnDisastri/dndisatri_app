// Service worker minimo: serve a rendere l'app installabile ("Aggiungi a
// schermata Home"). Non mette in cache le pagine di proposito: l'app è
// dinamica (Livewire, CSRF, sessione), e servire copie vecchie farebbe più
// danni che comodo. Qui si passa tutto alla rete.

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

// Un gestore fetch deve esserci per l'installabilità, ma lascia fare alla rete.
self.addEventListener('fetch', () => {});
