module.exports = {
  "globDirectory": "./",
  "globPatterns": [
    "system/css/**/*.css",
    "system/js/**/*.min.js",
    "plugins/widcard/*.{png,jpg,css,html,min.js,gif,jpeg}",
    "themes/**/*.min.js",
    "themes/**/concat_*.css"
  ],
  "swDest": "sw.js",
  "swSrc": "./sw-base.js",
  "globIgnores": [
    "_install/**",
    "feeds/**",
    "cache/**",
    "tmp/**",
    "tests/**",
    "system/images/flags/**",
    "system/js/external/tinymce/**",
  ]
};