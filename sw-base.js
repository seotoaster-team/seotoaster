importScripts('https://storage.googleapis.com/workbox-cdn/releases/3.0.0-beta.0/workbox-sw.js');
const STATIC_FILES = [
  '/',
  '/pwa-offline.html',
];

if (workbox) {
  self.addEventListener('install', function(event) {
    event.waitUntil(
      caches.open('static').then(cache => {
        cache.addAll(STATIC_FILES);
      })
    );
  });


  workbox.routing.registerRoute(
    routeData => routeData.event.request.headers.get('accept').includes('text/html'),
    args => fetch(args.event.request)
      .then(res => res)
      .catch(err =>
        {
          console.log("IN ERROR", err);
          return caches.match('/pwa-offline.html')
            .then(res =>  {
                console.log("IN CACHE", err);
               return res;
              }
            )
        }
      )
  );

  workbox.precaching.precacheAndRoute([]);
  
} else {
  console.log(`Boo! Workbox didn't load 😬`);
}