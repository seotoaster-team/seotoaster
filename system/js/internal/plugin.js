$(function() {
	//pluginCallback();
	$(document).on('click', '.plugin-control', function() {
		triggerPlugin('install', $(this));
	}).on('click', '.plugin-endis', function() {
		triggerPlugin('onoff', $(this));
	}).on('mouseenter', '.plugin-item', function () {
		$(this).find('.del-plugin').fadeIn(100);
		$(this).find('.readme-plugin').fadeIn(100);
	}).on('mouseleave', '.plugin-item', function() {
		$(this).find('.del-plugin').hide();
		$(this).find('.readme-plugin').hide();
	}).on('click', '.readme-plugin', function() {
		var pluginName = $(this).data('name');
		$.post($('#website_url').val() + 'backend/backend_plugin/readme/', {
			pluginName: pluginName
		}, function(response) {
			if(!response.error) {
				var readmeText = (typeof response.responseText == 'undefined' || response.responseText == 'success') ? 'No readme for this plugin provided' : response.responseText;
				var readmeDialog = $('<div style="overflow-y:auto; font-size: 14px; padding: 10px; background: #fff;" id="' + pluginName + '-readme">' + readmeText + '</div>');
				readmeDialog.dialog({
		            modal: true,
		            title: pluginName,
					width: 600,
					height: 250,
					resizable: false,
		            show: 'clip',
		            hide: 'clip',
		            buttons: [
		                {text: "Okay", click: function() {$(this).dialog("close")}}
		            ]
		        }).parent().css({
					background: '#DAE8ED'
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
		$('.plugin-control, .plugin-endis').button();
	})
}

function triggerPlugin(type, element) {
	url = $('#website_url').val() + ((type == 'install') ? 'backend/backend_plugin/triggerinstall/' : 'backend/backend_plugin/trigger/');
	$.ajax({
		url : url,
		type       : 'post',
		dataType   : 'json',
		data: {name : element.data('name')},
		beforeSend : function() {showSpinner();},
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
