importScripts('https://storage.googleapis.com/workbox-cdn/releases/3.0.0-beta.0/workbox-sw.js');
const STATIC_FILES = [
  '/',
  '/index.html',
  '/pwa-offline.html',
];

if (workbox) {
  workbox.routing.registerRoute(/.*(?:googleapis|gstatic)\.com.*$/,
    workbox.strategies.staleWhileRevalidate({
      cacheName: 'google-сache'
    })
  );

  workbox.routing.registerRoute(
    routeData => routeData.event.request.headers.get('accept').includes('text/html'),
    args => {
      return caches.match(args.event.request).then(response => {
      if (response) {
        return response;
      } else {
        return fetch(args.event.request)
          .then(res => caches.open(STATIC_FILES).then(cache => cache.addAll(STATIC_FILES)))
          .catch(err => caches.match('/pwa-offline.html').then(res => res));
      }
    })
  });

  workbox.precaching.precacheAndRoute([]);


  
} else {
  console.log(`Boo! Workbox didn't load 😬`);
}