$(function() {
    // plugin screen tabs
    $('#plugintab').tabs({
        active     : 0,
        beforeLoad : function( event, ui ) {
            ui.panel.addClass('plugins-list h425');
            ui.ajaxSettings.dataFilter = function(data) {
                ui.panel.html($.parseJSON(data).pluginsList);
                $('.plugin-item a.plugin-control, .plugin-item a.plugin-endis').button();
            };
            ui.jqXHR.done(function(){});
        }
    });

    // handling plugins controls
	$(document).on('click', '.plugin-control', function() {triggerPlugin('install', $(this));})
        .on('click', '.plugin-endis', function() {triggerPlugin('onoff', $(this));})
        .on('click', '.readme-plugin', function() {
            var pluginName = $(this).data('name');
            $.post($('#website_url').val() + 'backend/backend_plugin/readme/', {
                pluginName: pluginName
            }, function(response) {
                if(!response.error) {
                    var readmeText = (typeof response.responseText == 'undefined' || response.responseText == 'success') ? 'No readme for this plugin provided' : response.responseText;
                    var readmeDialog = $('<div class="readme-content content-footer" id="' + pluginName + '-readme">' + readmeText + '</div>');
                    readmeDialog.dialog({
                        modal: true,
                        title: pluginName,
                        width: 800,
                        height: 560,
                        resizable: false,
                        draggable : false,
                        show: 'clip',
                        hide: 'clip',
                        buttons: [
                            {text: "Okay", click: function() {$(this).dialog("close")}}
                        ]
                    });
                } else {
                    showMessage(response.responseText, true);
                }
            }, 'json');
	    });
});

function pluginCallback() {
	$.getJSON($('#website_url').val() + 'backend/backend_plugin/list/', function(response) {
		$('.plugins-list').html(response.pluginsList);
		$('a.plugin-control, a.plugin-endis').button();
	})
}

function triggerPlugin(type, element) {
	var url = $('#website_url').val() + ((type == 'install') ? 'backend/backend_plugin/triggerinstall/' : 'backend/backend_plugin/trigger/');
	$.ajax({
		url : url,
		type       : 'post',
		dataType   : 'json',
		data: {name : element.data('name')},
		beforeSend : function() {showSpinner('.plugins-list');},
		success: function(response) {
			hideSpinner();
			if(!response.error) {
				pluginCallback();
			} else {
				showMessage(response.responseText, true);
			}
		},
		error: function(err) {
			showMessage(err, true);
		}
	});
}
