define([
    'underscore',
    'backbone'
], function(_, Backbone){

    var ThemeModel = Backbone.Model.extend({
        urlRoot: $('#website_url').val() + 'api/toaster/themes/name/',
        initialize: function() {
            this.set({id: this.get('name')});
        }
    });

    return ThemeModel;
});
