importScripts('https://storage.googleapis.com/workbox-cdn/releases/3.0.0-beta.0/workbox-sw.js');
const STATIC_FILES = [
  '/',
  '/pwa-offline.html',
];

if (workbox) {
  self.addEventListener('install', function (event) {
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
      .catch(err => {
          return caches.match('/pwa-offline.html')
            .then(res => {
                return res;
              }
            )
        }
      )
  );

  workbox.precaching.precacheAndRoute([
  {
    "url": "system/css/reset-widgets.css",
    "revision": "7e5b22a1e5ca63ea132b98a16160b51a"
  },
  {
    "url": "system/css/reset.css",
    "revision": "9f94b6d0cc0bc238927e31ad3f0da8c6"
  },
  {
    "url": "system/css/seotoaster-reset.css",
    "revision": "041a45274f23007527eaff6dab58e375"
  },
  {
    "url": "system/css/seotoaster-ui.css",
    "revision": "d626eccbc3e6040623c275954afb01b0"
  },
  {
    "url": "system/css/seotoaster.css",
    "revision": "e6ce313092969ff91153b5a4f65f3dbf"
  },
  {
    "url": "system/js/external/backbone/backbone.min.js",
    "revision": "2ec02138cedec0a9dfdd99e225093aec"
  },
  {
    "url": "system/js/external/chosen/chosen.jquery.min.js",
    "revision": "8786a9ac3b0cc1f8e6621ea6c7f5eba5"
  },
  {
    "url": "system/js/external/hammer/hammer.min.js",
    "revision": "5e096613a4835a9f0756fd09d566e0ac"
  },
  {
    "url": "system/js/external/jquery/plugins/cycle/jquery.cycle2.min.js",
    "revision": "106b46232858402d20834cb644c6c997"
  },
  {
    "url": "system/js/external/jquery/plugins/cycle/jquery.cycle2.scrollVert.min.js",
    "revision": "87530d3bad811e94499f3c650f264134"
  },
  {
    "url": "system/js/external/jquery/plugins/cycle/jquery.cycle2.shuffle.min.js",
    "revision": "56f05d98f580b3c31c4f8b9e54d53a40"
  },
  {
    "url": "system/js/external/jquery/plugins/cycle/jquery.cycle2.swipe.min.js",
    "revision": "c9d5e3f8e2ae9f27a42b200871360640"
  },
  {
    "url": "system/js/external/jquery/plugins/cycle/jquery.cycle2.tile.min.js",
    "revision": "4362d146a331a2a51a5065c69ccb8fb5"
  },
  {
    "url": "system/js/external/jquery/plugins/DataTables/jquery.dataTables.min.js",
    "revision": "2b996d99c3fc71932ffc57acc97f03eb"
  },
  {
    "url": "system/js/external/jquery/plugins/lazyload/jquery.lazyload.min.js",
    "revision": "6b69ef8722e71ed0dc52488f919227a3"
  },
  {
    "url": "system/js/external/jquery/plugins/maskedinput/jquery.maskedinput.min.js",
    "revision": "9db7601708fd23748138bc7015676a05"
  },
  {
    "url": "system/js/external/jquery/plugins/mousewheel/jquery.mousewheel.min.js",
    "revision": "a3a0bce110217ef25288adf48d6db543"
  },
  {
    "url": "system/js/external/jquery/plugins/tmpl/jquery.tmpl.min.js",
    "revision": "2d3f81556ce2d37186392896d26aeba5"
  },
  {
    "url": "system/js/external/jquery/plugins/touchpunch/jquery.ui.touch-punch.min.js",
    "revision": "1e0adfa6441bc911392c10e9c96e2865"
  },
  {
    "url": "system/js/external/magnific-popup/jquery.magnific-popup.min.js",
    "revision": "d9267d6dda814fd767e1df7bfbe7eb57"
  },
  {
    "url": "system/js/external/require/require.min.js",
    "revision": "0d03cefe807fe4d4e6203926d174f42c"
  },
  {
    "url": "system/js/external/sisyphus/sisyphus.min.js",
    "revision": "e46827d23325a0ccf78b37105e126959"
  },
  {
    "url": "system/js/external/smoke/smoke.min.js",
    "revision": "dec3257e9584e1e558792ec033679a17"
  },
  {
    "url": "system/js/external/tabifier/tabifier.min.js",
    "revision": "dea34ff6a7c36e3b45bb11a7834857bf"
  },
  {
    "url": "system/js/external/underscore/underscore.min.js",
    "revision": "45635c8658599ecae698d0d45efc480d"
  },
  {
    "url": "system/js/external/waypoints/waypoints.min.js",
    "revision": "71967be36cbfcebea8e0d9cf91b83881"
  },
  {
    "url": "system/js/internal/adminPanelInit.min.js",
    "revision": "95b45988d5b7808616e8806452dfe843"
  },
  {
    "url": "system/js/internal/content.min.js",
    "revision": "a3ca1b0c16113c0f4be0f7179c65ce96"
  },
  {
    "url": "system/js/internal/deeplinks.min.js",
    "revision": "2c9f4d7ad4222eba5152294f2ecc89cd"
  },
  {
    "url": "system/js/internal/featuredarea.min.js",
    "revision": "b07190c92440a59ea21bbeb76df42e46"
  },
  {
    "url": "system/js/internal/organize.min.js",
    "revision": "a3083a4be0d4300fe8e45190d363b4ef"
  },
  {
    "url": "system/js/internal/page.min.js",
    "revision": "8d9f521dce2ee8fc74e2a455d49b40cb"
  },
  {
    "url": "system/js/internal/plugin.min.js",
    "revision": "d533600fd25554b320c384408dee2d90"
  },
  {
    "url": "system/js/internal/redirect.min.js",
    "revision": "dddf1f31ccf1c41a0d14cff6d4401218"
  },
  {
    "url": "system/js/internal/sculpting.min.js",
    "revision": "2da555f3a59f0089b50e830dd54bd7a8"
  },
  {
    "url": "system/js/internal/system.min.js",
    "revision": "14d5f647400cf4c37d013d3a499207f5"
  },
  {
    "url": "system/js/internal/theme.min.js",
    "revision": "d4988ed867ce31148f2ee37a1bee93fb"
  },
  {
    "url": "plugins/widcard/preview.jpg",
    "revision": "211148d92f180f5d5024fc05f3393b31"
  },
  {
    "url": "plugins/widcard/system/css/landing.css",
    "revision": "698386b29d66f610f27d043430458f72"
  },
  {
    "url": "plugins/widcard/system/css/widcard.css",
    "revision": "9623e14523be32d00b5b2efbc1c427f7"
  },
  {
    "url": "plugins/widcard/system/images/edit.png",
    "revision": "d95f1e60455e7c2fb5a435b1cdd9c25a"
  },
  {
    "url": "plugins/widcard/system/images/load.gif",
    "revision": "da104ec075ce9c1410d264983785e143"
  },
  {
    "url": "plugins/widcard/system/images/payway/002.jpeg",
    "revision": "5cd5d9240cf03a43455785406907a2d6"
  },
  {
    "url": "plugins/widcard/system/images/payway/01.png",
    "revision": "4efa872ca1087b2cd12b4b20d37bc43b"
  },
  {
    "url": "plugins/widcard/system/images/payway/02.png",
    "revision": "a1b085f6dc40f5b04bd5505d542903bd"
  },
  {
    "url": "plugins/widcard/system/images/payway/03.png",
    "revision": "86f23d157151a18c14c0b954a10f6bd7"
  },
  {
    "url": "plugins/widcard/system/images/payway/032.jpeg",
    "revision": "9df3f1ad880db8751547953354daed6a"
  },
  {
    "url": "plugins/widcard/system/images/payway/04.png",
    "revision": "8706299da592ba198bb9f3f4ce5721ef"
  },
  {
    "url": "plugins/widcard/system/images/payway/05.png",
    "revision": "bed5286bd6b854b932a2ce96de5d5cae"
  },
  {
    "url": "plugins/widcard/system/images/payway/06.png",
    "revision": "d6f53bb7378ddf711573d789d2cd3d7b"
  },
  {
    "url": "plugins/widcard/system/images/payway/07.png",
    "revision": "60259448dd791a5e3e43aac6415afd4e"
  },
  {
    "url": "plugins/widcard/system/images/payway/08.png",
    "revision": "710fc64abddcc376add723c71a96402b"
  },
  {
    "url": "plugins/widcard/system/images/payway/09.png",
    "revision": "59739957755c8fe466681f1cbd43b965"
  },
  {
    "url": "plugins/widcard/system/images/payway/10.png",
    "revision": "e251e56ea87349f81497639fb78b7436"
  },
  {
    "url": "plugins/widcard/system/images/payway/11.png",
    "revision": "fe16a28291bab2ea98ece99a2b3615f9"
  },
  {
    "url": "plugins/widcard/system/images/payway/12.png",
    "revision": "acfa8745a1eabff34314ed722f8fe768"
  },
  {
    "url": "plugins/widcard/system/js/widcard.min.js",
    "revision": "f14fdb92f36ab6c8737c4db989da340f"
  },
  {
    "url": "plugins/widcard/system/userdata/CorporateLogo.jpg",
    "revision": "2e27b40715a2aa7e7b85d852f287acca"
  },
  {
    "url": "plugins/widcard/system/userdata/CorporateLogo.png",
    "revision": "6156a02d95626497d9a9feb2da6e53fb"
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
  }
]);

}