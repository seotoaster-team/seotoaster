define([
    'underscore',
    'backbone'
], function(_, Backbone) {

    var themeView = Backbone.View.extend({
        className  : 'themebox',
        tagName    : 'div',
        template   : _.template($('#theme-box').text()),
        events     : {
            'mouseenter': 'toggleControlls',
            'mouseleave': 'toggleControlls',
            'click button.apply-button': 'applyThemeAction',
            //'click a.apply-theme': 'applyThemeAction',
            'click a.lnk-delete': 'deleteThemeAction',
            'mouseenter a.lnk-download': 'downloadThemeAction',
            'mouseleave .download-options': function(e) {this.$(e.currentTarget).hide(); }
        },
        initialize : function() {
            this.model.view = this;
        },
        toggleControlls: function() {
            this.$el.toggleClass('hovered');
            this.$('.lnk-download').fadeToggle();
            this.$('.lnk-delete').fadeToggle();
            this.$('.apply-button').fadeToggle();
        },
        applyThemeAction: function() {
            var self = this;
            showConfirm('Are you sure you want to apply "' + self.model.get('name') + '" theme?', function() {
                showSpinner();
                self.model.save(null, {
                    success: function(model) {
                        hideSpinner();
                        window.themesModule.themes.map(function(theme) {
                            theme.set({isCurrent: false});
                            if(theme.get('name') == model.get('name')) {
                                theme.set({isCurrent: true});
                            }
                            return theme;
                        });
                        window.themesModule.render();
                        showMessage('Theme "' + model.get('name') + '" applied successfully! Click close or simply reload a page for the changes to take affect', false, 3000);
                    },
                    error: function(model, xhr, options) {
                        hideSpinner();
                        var errorMessage = (xhr.responseText.length) ? xhr.responseText : 'Can not apply theme "' + self.model.get('name') + '"! Something went wrong...';
                        showMessage(errorMessage, true);
                    }
                });
            });
        },
        downloadThemeAction: function(e) {
            this.$(e.currentTarget).next('.download-options').show();
        },
        deleteThemeAction: function(e) {
            var self = this;
            var name = $(e.currentTarget).data('name');
            showConfirm('Are you sure you want to remove "' + this.model.get('name') + '" theme?', function() {
                showSpinner();
                var theme = _.first(window.themesModule.themes.where({name: name}));
                //console.log(theme.isNew());
                theme.destroy({
                    wait: true,
                    success: function() {
                        hideSpinner();
                    },
                    error: function(model, response) {
                        hideSpinner();
                        showMessage(response.responseText, true);
                    }
                });
            });
        },
        render: function(){
            this.$el.html(this.template(this.model.toJSON()));
            return this;
        }
    });

    return themeView;

});