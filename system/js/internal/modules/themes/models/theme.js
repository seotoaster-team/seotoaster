define([
    'underscore',
    'backbone'
], function(_, Backbone){

    var ThemeModel = Backbone.Model.extend({
        urlRoot: function() {
            var url  = $('#website_url').val() + 'api/toaster/themes/';
            var name = this.get('name');
            return url + 'name/' + name;
        },
        initialize: function() {
            this.set({id: this.get('name')});
        }
    });

    return ThemeModel;
});
