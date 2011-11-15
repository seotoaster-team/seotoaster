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
		$(this).find('.readme-plugin').fadeIn(100);
	}).live('mouseleave', function() {
		$(this).find('.del-plugin').hide();
		$(this).find('.readme-plugin').hide();
	})
})

function pluginCallback() {
	$.getJSON($('#website_url').val() + 'backend/backend_plugin/list/', function(response) {
		$('#plugins-list').html(response.pluginsList);
		$('.plugin-control, .plugin-endis').button();
	})
}
$('.readme-plugin').live("click", function(){
    var pluginName = $(this).attr('id');
    $.post($('#website_url').val() + 'backend/backend_plugin/readme/', {
         pluginName : pluginName
    },
    function(responce) {
         if(responce.error == '0'){
            var pluginReadme = '<style>.ui-dialog.ui-widget.ui-widget-content.ui-corner-all.ui-draggable{width:600px !important;}</style><div style="max-height:300px !important; font-size:14px;">'+responce.responseText+'</div>';
            showModalMessage(pluginName.charAt(0).toUpperCase() + pluginName.slice(1), pluginReadme)
            $('div.ui-dialog-titlebar.ui-widget-header.ui-corner-all.ui-helper-clearfix').removeClass('ui-corner-all').addClass('ui-corner-top');
         }
         else{
            $('#ajax_msg').text(readme.responseText).show();
            $("#ajax_msg").removeClass('success').addClass('ui-state-error');
         }
    }, 'json')
});
