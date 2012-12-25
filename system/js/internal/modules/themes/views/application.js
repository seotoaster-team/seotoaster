define([
    'underscore',
    'backbone',
    '../collections/themes',
    './theme'
], function(_, Backbone, ThemesCollection, ThemeView) {

    var themesAppView = Backbone.View.extend({
        el: $('div.seotoaster'),

        themes: null,

        events: {

        },

        initialize: function() {
            showSpinner();
            var self = this;

            this.themes = new ThemesCollection();
            this.themes.on('reset', this.render, this);
            this.themes.on('destroy', this.render, this);

            this.themes.fetch({
                error: function(model, xhr) {
                    hideSpinner();
                    showMessage(xhr.responseText, true);
                    this.$('#themes-list').find('div').text(xhr.responseText);
                }
            });

            // this trigger is acting when new theme was uploaded
            $(document).on('updateContent', function() {
                self.themes.fetch({
                    success: function() {
                        showMessage('Congratulations, your new theme has been installed. Select a theme by clicking apply button then close this window to view your new theme.', false, 5000);
                    },
                    error: function(collection, xhr, options) {
                        showMessage(xhr.responseText, true);
                    }
                });
            });

        },

        renderThemes: function() {
            var themesContainer = this.$('#themes-list');
            themesContainer.empty();
            this.themes.each(function(theme) {
                var themeView = new ThemeView({model: theme});
                if(theme.get('isCurrent')) {
                    themesContainer.prepend(themeView.render().el);
                } else {
                    themesContainer.append(themeView.render().el);
                }
            })
            return this;
        },
        render: function() {
            this.renderThemes();
            hideSpinner();
            return this;
        }
    });

    return themesAppView;

});