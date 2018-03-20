importScripts('https://storage.googleapis.com/workbox-cdn/releases/3.0.0/workbox-sw.js');
//# sourceMappingURL=workbox-sw.js.map
const STATIC_FILES = [
  '/',
  '/pwa-offline.html',
  '/tmp/offline.concat.min.css'
];

if (workbox) {

  workbox.skipWaiting();
  workbox.clientsClaim();

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
      "revision": new Date().toDateString(),
    },
    {
      "url": "tmp/offline.concat.min.css",
      "revision":  new Date().toDateString(),
    },
  ]);
}
