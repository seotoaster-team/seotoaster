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
    "url": "themes/mobile-app/js/plugin/hammer.min.js",
    "revision": "2a78bcd922aa039e68ba0b699ac95a20"
  },
  {
    "url": "themes/mobile-app/js/plugin/jquery.carousel.min.js",
    "revision": "33635ab9f6dfd630275772d35774bab8"
  },
  {
    "url": "themes/mobile-app/js/plugin/jquery.chosen.min.js",
    "revision": "a7460b8e26cabfe0bbfed76ea19cfd09"
  },
  {
    "url": "themes/mobile-app/js/plugin/jquery.cycle2.min.js",
    "revision": "dc78931466a6a3bdb7e4f8aef5450b02"
  },
  {
    "url": "themes/mobile-app/js/scripts.min.js",
    "revision": "06bc6555cf4ccfe39506f31e8f19e8ab"
  },
  {
    "url": "themes/mobile-app/js/system/flexkit.min.js",
    "revision": "59de8ea88ef9bf7d1604316d8986a1a1"
  },
  {
    "url": "themes/mobile-app/js/system/ie-pack.min.js",
    "revision": "280fca82ee1dc811dab1111c31430a82"
  },
  {
    "url": "themes/mobile-app/css/concat_27a21354321dfb.css",
    "revision": "0765f5e164cb8ec96c33242a3edbd88f"
  },
  {
    "url": "themes/mobile-app/css/concat_6a992d5529f459.css",
    "revision": "0765f5e164cb8ec96c33242a3edbd88f"
  },
  {
    "url": "themes/mobile-app/css/concat_c21f969b5f03d3.css",
    "revision": "0765f5e164cb8ec96c33242a3edbd88f"
  },
  {
    "url": "themes/mobile-app/css/concat_c4ef352f74e502.css",
    "revision": "0765f5e164cb8ec96c33242a3edbd88f"
  }
]);
  
} else {
  console.log(`Boo! Workbox didn't load 😬`);
}