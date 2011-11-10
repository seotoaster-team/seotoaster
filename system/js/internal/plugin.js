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

	$('.plugin-item').live('mouseenter', function (){
		$(this).find('.del-plugin').fadeIn(100);
	}).live('mouseleave', function() {
		$(this).find('.del-plugin').hide();
	})
})

function pluginCallback() {
	$.getJSON($('#website_url').val() + 'backend/backend_plugin/list/', function(response) {
		$('#plugins-list').html(response.pluginsList);
		$('.plugin-control, .plugin-endis').button();
	})
}