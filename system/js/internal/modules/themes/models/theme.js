define([
    'underscore',
    'backbone'
], function(_, Backbone){

    var ThemeModel = Backbone.Model.extend({
        urlRoot: function() {
            return $('#website_url').val() + 'api/toaster/themes/name/'
        },
//        initialize: function() {
//            this.set({id: this.get('name')});
//        }
        idAttribute: "name"
    });

    return ThemeModel;
});
