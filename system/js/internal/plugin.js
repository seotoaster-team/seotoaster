$(function() {
	pluginCallback();
	$('.plugin-control').live('click', function() {
		var lnk = $(this);
		$('#ajax_msg').text('Working').show();
		$.post($('#website_url').val() + 'backend/backend_plugin/triggerinstall/', {
			name : lnk.attr('id')
		},
		function(response) {
			$('#ajax_msg').fadeOut();
			pluginCallback();
		})
	})

	$('.plugin-endis').live('click', function() {
		var lnk = $(this);
		$('#ajax_msg').text('Working').show();
		$.post($('#website_url').val() + 'backend/backend_plugin/trigger/', {
			name : lnk.data('name')
		},
		function(response) {
			$('#ajax_msg').fadeOut()
			pluginCallback();
		})
	})
})

function pluginCallback() {
	$.getJSON($('#website_url').val() + 'backend/backend_plugin/list/', function(response) {
		$('#plugins-list').html(response.pluginsList);
		$('.plugin-control, .plugin-endis').button();
	})
}