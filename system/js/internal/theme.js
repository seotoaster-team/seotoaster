$(function() {
	$('#frm_template').submit(saveTemplate);

	$('#templatelist').delegate('div.template_preview', 'click', function(){
		$.post(
			$('#website_url').val()+'backend/backend_theme/gettemplate/',
			{'listtemplates': $(this).find('input[name="template-id"]').val()},
			function(response){
				if (response.error != false){
//					var dialogTitle = 'Edit template';
//					$dialog = $('#frm_template').closest(':ui-dialog');
//					if ($dialog.dialog("option", "title") != dialogTitle ) $dialog.dialog("option", "title", dialogTitle);
					$('#frm_template').find('#title').val(response.responseText.name);
					$('#frm_template').find('#content').val(response.responseText.content);
					$('#frm_template').find('#template_id').val(response.responseText.name);
					var tpreview;
					if (response.responseText.preview) {
						tpreview = response.responseText.preview;
						$('#frm_template').find('#preview_image').val(tpreview);
					} else {
						tpreview = '/system/images/no_image.png';
						$('#frm_template').find('#preview_image').val('');
					}
					$('#template_preview').attr('src', $('#website_url').val()+tpreview);
				}
			},
			'json'
		);
		$('#templatelist').slideUp();
	}).delegate('div.template_delete', 'click', function(){
		if (confirm('Do you really want to delete this template?')){
			deleteTemplate($(this).closest('div.template_item'));
		}
		return false;
	})
})

function saveTemplate() {
	var ajaxMsg = $('#ajax_msg');
	
	$.ajax({
		url        : $(this).attr('action'),
		type       : 'post',
		dataType   : 'json',
		data       : $(this).serialize(),
		beforeSend : function() {
			ajaxMsg.fadeIn().text('Working...');
		},
		success : function(response) {
			if (response.error != true){
				ajaxMsg.text('Template saved');
				if (response.responseText == 'new') {
					$(this).find('input').val('');
				}
			} else {
				ajaxMsg.html(response.responseText.join('. '));
			}
			//top.location.reload();
		},
		error: function() {
			ajaxMsg.text('Error occured');
		}
	})
	return false;
}

function deleteTemplate(templateContainer) {
	$.post(
		$('#website_url').val()+'backend/backend_theme/deletetemplate/',
		{"id": templateContainer.find('input[name="template-id"]').val()},
		function(response) {
			if (response.error == true){
				templateContainer.remove();
			} 
			$('#ajax_msg').text(response.responseText).fadeIn().delay(5000).fadeOut();
		},
		'json'
	);
}
