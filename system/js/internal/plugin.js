$(function () {
    // plugin screen tabs
    $('#plugintab').tabs({
        active: 0,
        beforeLoad: function (event, ui) {
            ui.panel.addClass('plugins-list h425 column_5 full-width');
            ui.ajaxSettings.dataFilter = function (data) {
                ui.panel.html($.parseJSON(data).pluginsList);
            };
        }
    });

    // handling plugins controls
    $(document).on('click', '.plugin-control', function () {
        var typeOperation = $(this).data('operation');
        if (typeOperation == 'uninstall'){
            var self= $(this);
            smoke.confirm('You are about to remove an item. Are you sure?', function(e){
                if(e){
                    triggerPlugin('install', self);
                }
            }, {classname : "error", 'ok' : 'Yes', 'cancel' : 'No'});

        } else {
            triggerPlugin('install', $(this));
        }
    })
        .on('click', '.plugin-endis', function () {
            triggerPlugin('onoff', $(this));
        })
        .on('click', '.readme-plugin', function () {
            var pluginName = $(this).data('name');
            $.post($('#website_url').val() + 'backend/backend_plugin/readme/', {
                pluginName: pluginName
            }, function (response) {
                if (response.error) {
                    showMessage(response.responseText, true);
                } else {
                    var readmeDialog;
                    readmeDialog = $('<div id="' + pluginName + '-readme" class="readme-content content-footer">')
                        .html(response.responseText);

                    readmeDialog.dialog({
                        modal: true,
                        title: pluginName,
                        width: 800,
                        height: 560,
                        resizable: false,
                        draggable: false,
                        show: 'clip',
                        hide: 'clip',
                        buttons: [
                            {text: "Okay", 'class': 'btn', click: function () {
                                $(this).dialog("close");
                            }}
                        ]
                    });
                }
            }, 'json');
        });
});

function pluginCallback() {
	$.getJSON($('#website_url').val() + 'backend/backend_plugin/list/', function(response) {
		$('.plugins-list').html(response.pluginsList);
	})
}

function triggerPlugin(type, element) {
    var url = $("#website_url").val();
    if (type === 'install') {
        url += "backend/backend_plugin/triggerinstall/";
    } else {
        url += "backend/backend_plugin/trigger/";
    }
    $.ajax({
        url: url,
        type: 'post',
        dataType: 'json',
        data: {name: element.data('name')},
        beforeSend: function () {
            showSpinner();
        },
        success: function (response) {
            hideSpinner();
            if (!response.error) {
                pluginCallback();
            } else {
                showMessage(response.responseText, true);
            }
        },
        error: function (err) {
            showMessage(err.responseText, true);
        }
    });
}
