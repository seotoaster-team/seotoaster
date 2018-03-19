importScripts('https://storage.googleapis.com/workbox-cdn/releases/3.0.0/workbox-sw.js');
//# sourceMappingURL=workbox-sw.js.map
const STATIC_FILES = [
  '/',
  '/pwa-offline.html',
];

if (workbox) {

  workbox.routing.registerRoute(
    routeData => routeData.event.request.headers.get('accept').includes('text/html'),
    args => fetch(args.event.request)
      .then(res => res)
      .catch(() => caches.match('/pwa-offline.html')
        .then(res => res))
  );

  workbox.routing.registerRoute(/.*(?:woff|ttf)$/,
    workbox.strategies.staleWhileRevalidate({cacheName: 'fonts'})
  );

  workbox.routing.registerRoute(/plugins\/widcard\/system\/userdata\/.*\.(png|jpg)$/,
    workbox.strategies.staleWhileRevalidate({cacheName: 'images'})
  );

  workbox.precaching.precacheAndRoute([
  {
    "url": "pwa-offline.html",
    "revision": "2e27b40715a2aa7e7b85d852f287acca"
  },
  {
    "url": "tmp/offline.concat.min.css",
    "revision": "2e27b40715a2aa7e7b85d852f287accq"
  },
]);
}