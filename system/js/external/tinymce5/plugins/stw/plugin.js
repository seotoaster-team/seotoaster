tinymce.PluginManager.add('stw', function(editor, url) {

    function getValues() {
        var widgetListMenuitem = [],
            widgetListNestedmenuitem = [],
            nestedmenuNames = [],
            nestedmenuNamesOrig = [];

        $.ajax({
            type: 'post',
            url: $('#website_url').val() + 'backend/backend_content/loadwidgets/',
            success: function(widgets) {
                for(var i in widgets) {
                    for(var j in widgets[i]) {
                        var newWidget = {};
                        newWidget['type'] = 'menuitem';
                        if(typeof widgets[i][j].alias != 'undefined') {
                            if(typeof widgets[i][j].group != 'undefined') {
                                newWidget['type'] = 'nestedmenuitem';
                                newWidget['subtype'] = widgets[i][j].group.toLowerCase();
                                newWidget['subtype'] = newWidget['subtype'].replace(/\s/g, '');

                                if(nestedmenuNames.indexOf(newWidget['subtype']) === -1) {
                                    nestedmenuNames.push(newWidget['subtype']);
                                    nestedmenuNamesOrig.push(widgets[i][j].group);
                                }
                            }

                            newWidget['text'] = widgets[i][j].alias;
                            newWidget['value'] = '{$' + widgets[i][j].option + '}';
                        }
                        else {
                            newWidget['text'] = widgets[i][j];
                            newWidget['value'] = '{$' + widgets[i][j] + '}';
                        }

                        if(newWidget['type'] == 'menuitem') {
                            widgetListMenuitem.push(newWidget);
                        } else {
                            widgetListNestedmenuitem.push(newWidget);
                        }


                    }
                }
            },
            dataType: 'json'
        });
        return {
            widgetListMenuitem,
            widgetListNestedmenuitem,
            nestedmenuNames,
            nestedmenuNamesOrig
        };
    }

    var shortCutValues = getValues();

    editor.ui.registry.addMenuButton('stw', {
        text: 'Useful shortcuts',
        fetch: function (callback) {
            var items = [];

            if(shortCutValues.nestedmenuNames) {
                var subItems = [];
                for (let fieldNameNestedmenuitem in shortCutValues.widgetListNestedmenuitem) {
                    var subMenuItem = {
                        type: shortCutValues.widgetListNestedmenuitem[fieldNameNestedmenuitem].type,
                        subtype: shortCutValues.widgetListNestedmenuitem[fieldNameNestedmenuitem].subtype,
                        text: shortCutValues.widgetListNestedmenuitem[fieldNameNestedmenuitem].text,
                        value:shortCutValues.widgetListNestedmenuitem[fieldNameNestedmenuitem].value
                    }

                    subItems.push(subMenuItem);
                }

                for (let i = 0; i < shortCutValues.nestedmenuNames.length; i += 1) {

                    let tmpsubMenuArr = [];
                    for (let sItemKey in subItems) {

                        if(subItems[sItemKey].subtype == shortCutValues.nestedmenuNames[i]){
                            var prePushItem = {
                                type: 'menuitem',//subItems[sItemKey].type,
                                text: subItems[sItemKey].text,
                                value: subItems[sItemKey].value,
                                onSetup: function(buttonApi) {
                                    var self = this;
                                    this.onAction = function() {
                                        editor.insertContent(self.data.value);
                                    };
                                }
                            }

                            tmpsubMenuArr.push(prePushItem);
                        }
                    }

                    var subMenuFinalObj = {
                        type: 'nestedmenuitem',
                        text: shortCutValues.nestedmenuNamesOrig[i],
                        getSubmenuItems: function () {
                           return tmpsubMenuArr;
                        }
                    }

                    items.push(subMenuFinalObj);
                }
            }

            for (let fieldNameMenuitem in shortCutValues.widgetListMenuitem) {
                var menuItem = {
                    type: shortCutValues.widgetListMenuitem[fieldNameMenuitem].type,
                    text: shortCutValues.widgetListMenuitem[fieldNameMenuitem].text,
                    value:shortCutValues.widgetListMenuitem[fieldNameMenuitem].value,
                    onSetup: function() {
                        var self = this;
                        this.onAction = function() {
                            editor.insertContent(self.data.value);
                        };
                    },
                };

                items.push(menuItem);
            }

            callback(items);
        }
    });

    return {
        getMetadata: function () {
            return  {
                name: "stw plugin",
                url: ""
            };
        }
    };

});
