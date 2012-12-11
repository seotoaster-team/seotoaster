require.config({
    deps: ['modules/themes/index'],
    paths: {
        'underscore'         : '../external/underscore/underscore.min',
        'backbone'           : '../external/backbone/backbone.min'
    },
    shim: {
        'underscore': {exports: '_'},
        'backbone' : {
            deps: ['underscore'],
            exports: 'Backbone'
        }
    }
});