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

  workbox.routing.registerRoute('/pwa-offline.html',
    workbox.strategies.staleWhileRevalidate({cacheName: 'static'})
  );

  workbox.routing.registerRoute(/.*(?:min\.js|\.css)$/,
    workbox.strategies.staleWhileRevalidate({cacheName: 'min-js-css'})
  );

  workbox.routing.registerRoute(/.*(?:woff|ttf)$/,
    workbox.strategies.staleWhileRevalidate({cacheName: 'fonts'})
  );

  workbox.precaching.precacheAndRoute([
  {
    "url": "pwa-offline.html",
    "revision": "2e27b40715a2aa7e7b85d852f287acca"
  },
  {
    "url": "plugins/widcard/system/userdata/CorporateLogo.jpg",
    "revision": "2e27b40715a2aa7e7b85d852f287acca"
  },
  {
    "url": "plugins/widcard/system/userdata/CorporateLogo.png",
    "revision": "1f3d18606a60eb754716dcf010012e62"
  },
  {
    "url": "plugins/widcard/system/userdata/icons/app-icon-144x144.png",
    "revision": "74d2028677c4c50f0b007440872db638"
  },
  {
    "url": "plugins/widcard/system/userdata/icons/app-icon-16x16.png",
    "revision": "0a47be2bcb49458062fb0e2a740466b6"
  },
  {
    "url": "plugins/widcard/system/userdata/icons/app-icon-192x192.png",
    "revision": "beca3a91feb5522a466215919aa8b5db"
  },
  {
    "url": "plugins/widcard/system/userdata/icons/app-icon-256x256.png",
    "revision": "7d7afa3bc08cc32712eddd37c7315cd8"
  },
  {
    "url": "plugins/widcard/system/userdata/icons/app-icon-32x32.png",
    "revision": "a81bbaa437bf87b7f55c450a2a7e03db"
  },
  {
    "url": "plugins/widcard/system/userdata/icons/app-icon-384x384.png",
    "revision": "4545c27938f5202867d0e6bd72367b66"
  },
  {
    "url": "plugins/widcard/system/userdata/icons/app-icon-48x48.png",
    "revision": "3427bfafd19e2cf3fd906ad75a87898d"
  },
  {
    "url": "plugins/widcard/system/userdata/icons/app-icon-512x512.png",
    "revision": "5c5d4f3854994871ab6731c8926ff977"
  },
  {
    "url": "plugins/widcard/system/userdata/icons/app-icon-96x96.png",
    "revision": "ac90139ceb90ae8135474f4a5683084c"
  },
  {
    "url": "plugins/widcard/system/userdata/icons/apple-icon-114x114.png",
    "revision": "e40ab5fd3bac9e630b843c58dd6979ad"
  },
  {
    "url": "plugins/widcard/system/userdata/icons/apple-icon-120x120.png",
    "revision": "ad640a0d584d9670c500551c9ed6eb8b"
  },
  {
    "url": "plugins/widcard/system/userdata/icons/apple-icon-144x144.png",
    "revision": "74d2028677c4c50f0b007440872db638"
  },
  {
    "url": "plugins/widcard/system/userdata/icons/apple-icon-152x152.png",
    "revision": "a2240aa22e1b535fd4d813811e574e7f"
  },
  {
    "url": "plugins/widcard/system/userdata/icons/apple-icon-180x180.png",
    "revision": "51b46fb2201b1c0689e4792467fe8240"
  },
  {
    "url": "plugins/widcard/system/userdata/icons/apple-icon-57x57.png",
    "revision": "63951d7e57962f6e6fcce6e7ba670bbb"
  },
  {
    "url": "plugins/widcard/system/userdata/icons/apple-icon-60x60.png",
    "revision": "53591eb908a52404ac9bfc22d93eb75a"
  },
  {
    "url": "plugins/widcard/system/userdata/icons/apple-icon-72x72.png",
    "revision": "ac90139ceb90ae8135474f4a5683084c"
  },
  {
    "url": "plugins/widcard/system/userdata/icons/apple-icon-76x76.png",
    "revision": "0f051387e5a10ab52440a93ff149121a"
  },
]);
}