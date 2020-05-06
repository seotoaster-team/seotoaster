/**
 *  ATTENTION: Do not format this file.
 *  This file can be automatically modified from the widcard and notifier plugins.
 */

const revision = 'pwa03202018';
importScripts('https://storage.googleapis.com/workbox-cdn/releases/3.0.0/workbox-sw.js');

if (typeof workbox !== 'undefined') {

  workbox.routing.registerRoute(
    routeData => routeData.event.request.headers.get('accept').includes('text/html'),
    args => fetch(args.event.request)
      .then(res => res)
      .catch(() => {
        return caches.match(args.event.request)
          .then(res => {
            return res || caches.match('/pwa-offline.html').then(res => res);
          })
      })
  );

  workbox.routing.registerRoute(/.*(?:woff|ttf)$/,
    workbox.strategies.staleWhileRevalidate({cacheName: 'fonts'})
  );

  workbox.routing.registerRoute(/\/plugins\/widcard\/system\/userdata\/.*\.(png|jpg)$/,
    workbox.strategies.staleWhileRevalidate({cacheName: 'images'})
  );

  workbox.precaching.precacheAndRoute([
    {
      "url": "/",
      "revision": revision,
    },
    {
      "url": "/pwa-offline.html",
      "revision": revision,
    },
    {
      "url": "/tmp/offline.concat.min.css",
      "revision": revision,
    },
  ]);
}

self.addEventListener('notificationclick', event => {
  //TODO: Rewrite it.
  const notification = event.notification;
  const action = event.action;
  if (action === 'confirm') {
    console.log('Confirm was chosen');
  } else {
    console.log(action);
    event.waitUntil(
      clients.matchAll()
        .then(clis => {
          const client = clis.find(c => c.visibilityState === 'visible');
          if (client !== undefined && notification.data && notification.data.url) {
            client.navigate(notification.data.url);
          } else if (notification.data && notification.data.url) {
            clients.openWindow(notification.data.url);
          }
        })
    )
  }
  notification.close();
});

self.addEventListener('notificationclose', event => {
  console.log('Notification was closed', event);
});

self.addEventListener('push', event => {
  let data = {title: 'News', content: 'News was added', openUrl: '/'};
  if (event.data) {
    data = JSON.parse(event.data.text());
  }

  const options = {
    body: data.content,
    icon: '/plugins/widcard/system/userdata/icons/app-icon-96x96.png',
    badge: '/plugins/widcard/system/userdata/icons/app-icon-96x96.png',
    data: {
      url: data.openUrl
    }
  };
  if (data.image !== 'undefined') {
    options.image = data.image;
  }

  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});