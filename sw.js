/**
 *  ATTENTION: Do not format this file.
 *  This file can be automatically modified from the widcard and notifier plugins.
 */
importScripts('https://storage.googleapis.com/workbox-cdn/releases/5.1.2/workbox-sw.js');
const {precacheAndRoute} = workbox.precaching;
const {registerRoute} = workbox.routing;
const {StaleWhileRevalidate} = workbox.strategies;

if (typeof workbox !== 'undefined') {

    const fontsStrategy = new StaleWhileRevalidate({cacheName: 'fonts'});
    const imagesStrategy = new StaleWhileRevalidate({cacheName: 'images'});

    registerRoute(
        routeData => routeData.event.request.headers.get('accept').includes('text/html'),
        args => {
            try {
                try {
                    var decodedUrl = decodeURIComponent(escape(decodeURIComponent(args.event.request.url)));
                } catch (e) {
                    var decodedUrl = decodeURIComponent(unescape(decodeURIComponent(args.event.request.url)));
                }
                var newRequest = new Request(decodedUrl,
                    {
                        method: args.event.request.method,
                        headers: args.event.request.headers,
                        destination: args.event.request.destination,
                        referrer: args.event.request.referrer,
                        referrerPolicy: args.event.request.referrerPolicy,
                        mode: 'cors',
                        credentials: args.event.request.credentials,
                        cache: args.event.request.cache,
                        redirect: args.event.request.redirect,
                        integrity: args.event.request.integrity,
                        keepalive: args.event.request.keepalive,
                        signal: args.event.request.signal,
                        isHistoryNavigation: args.event.request.isHistoryNavigation,
                        bodyUsed: args.event.request.bodyUsed,
                    });
            } catch (ex) {
                console.log(ex);
            }
            return fetch(newRequest)
                .then(res => res)
                .catch(() => {
                    return caches.match(newRequest)
                        .then(res => {
                            return res || caches.match('/pwa-offline.html').then(res => res);
                        })
                })
        });

    registerRoute(/.*(?:woff|ttf)$/,
        fontsStrategy
    );
    registerRoute(/\/plugins\/widcard\/system\/userdata\/.*\.(png|jpg)$/,
        imagesStrategy
    );

    precacheAndRoute([
        {
            "url": "/",
            "revision": null,
        },
        {
            "url": "/pwa-offline.html",
            "revision": null,
        },
        {
            "url": "/tmp/offline.concat.min.css",
            "revision": null,
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