tinymce.PluginManager.add('stw', function(editor, url) {

    function getValues() {
        var widgetList = new Array();
        $.ajax({
            type: 'post',
            url: $('#website_url').val() + 'backend/backend_content/loadwidgets/',
            success: function(widgets) {
                for(var i in widgets) {
                    for(var j in widgets[i]) {
                        var newWidget = {};
                        if(typeof widgets[i][j].alias != 'undefined') {
                            newWidget['text'] = widgets[i][j].alias;
                            newWidget['value'] = '{$' + widgets[i][j].option + '}';
                        }
                        else {
                            newWidget['text'] = widgets[i][j];
                            newWidget['value'] = '{$' + widgets[i][j] + '}';
                        }
                        widgetList.push(newWidget)
                    }
                }
                //remove existing menu if it is already rendered
                if(button.menu){
                    button.menu.remove();
                    button.menu = null;
                }

                button.settings.values = button.settings.menu = widgetList;
            },
            dataType: 'json'
        });
        return widgetList;
    }

    // Add a button that opens a window
    editor.addButton('stw', {
        type: 'listbox',
        text: 'Useful shortcuts',
        icon : false,
        values: getValues(),
        onselect: function() {
            //insert key
            editor.insertContent(this.value());

            //reset selected value
            this.value(null);
        },
        onPostRender: function() {
            button = this;
        }
    });
});