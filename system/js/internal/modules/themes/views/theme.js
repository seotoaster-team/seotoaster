define([
    'underscore',
    'backbone'
], function(_, Backbone) {

    var themeView = Backbone.View.extend({
        className  : 'themebox',
        tagName    : 'div',
        template   : _.template($('#theme-box').text()),
        events     : {
            'click button.apply-button': 'applyThemeAction',
            'click a.lnk-delete': 'deleteThemeAction'//,
        },
        initialize : function() {
            this.model.view = this;
        },
        applyThemeAction: function() {
            var self = this,
                attrs = {},
                callbacks = {
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
                        showMessage('Theme "' + model.get('name') + '" applied successfully! Click close or simply reload a page for the changes to take affect', false, 5000);
                    },
                    error: function(model, xhr, options) {
                        hideSpinner();
                        var errorMessage = (xhr.responseText.length) ? xhr.responseText : 'Can not apply theme "' + self.model.get('name') + '"! Something went wrong...';
                        showMessage(errorMessage, true);
                    }
                },
                save = function(){
                    showSpinner();
                    self.model.save(attrs, callbacks);
                };

            showConfirm('Are you sure you want to apply "' + self.model.get('name') + '" theme?', function() {
                if (self.model.has('hasData') && self.model.get('hasData')){
                    showConfirm('<p>This theme contains demo data. Do you want to install it?</p><p>All current website content will be removed</p>',
                    function(){
                        attrs = {applyData: true};
                        save()
                    }, save);
                } else {
                    save()
                }
            });
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