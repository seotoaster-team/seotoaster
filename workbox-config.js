module.exports = {
  "globDirectory": "./",
  "globPatterns": [
    "system/css/**/*.css",
    "system/js/**/*.min.js",
    "plugins/widcard/**/*.{png,jpg,css,html,min.js,gif,jpeg}",
  ],
  "swDest": "sw.js",
  "swSrc": "./sw-base.js",
  "globIgnores": [
    "_install/**",
    "feeds/**",
    "cache/**",
    "tmp/**",
    "themes/**",
    "tests/**",
    "system/images/flags/**",
    "system/js/external/tinymce/**",
  ]
};
