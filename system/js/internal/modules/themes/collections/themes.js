define([
    'underscore',
    'backbone',
    '../models/theme'
], function(_, Backbone, ThemeModel){
    var themesCollection = Backbone.Collection.extend({
        model : ThemeModel,
        url   : function() {
            return $('#website_url').val() + 'api/toaster/themes/';
        }
    });
    return themesCollection;
});