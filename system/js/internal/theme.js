$(function() {
	$('#frm_template').submit(saveTemplate);

	$('#templatelist').delegate('div.template_preview', 'click', function(){
		$.post(
			$('#website_url').val()+'backend/backend_theme/gettemplate/',
			{'listtemplates': $(this).find('input[name="template-id"]').val()},
			function(response){
				if (response.done == true){
					var dialogTitle = 'Edit template';
					$dialog = $('#frm_template').closest(':ui-dialog');
					if ($dialog.dialog("option", "title") != dialogTitle ) $dialog.dialog("option", "title", dialogTitle);
					$('#frm_template').find('#title').val(response.template.name);
					$('#frm_template').find('#content').val(response.template.content);
					$('#frm_template').find('#template_id').val(response.template.name);
					var tpreview = response.template.preview || '/system/images/no_image.png';
					$('#frm_template').find('#preview_image').val(tpreview);
					$('#template_preview').attr('src', $('#website_url').val()+tpreview);
				}
			},
			'json'
		);
		$('#templatelist').slideUp();
	})
		.delegate('div.template_delete', 'click', function(){
			if (confirm('Do you really want to delete this template?')){
				deleteTemplate($(this).closest('div.template_item'));
			}
			return false;
		})
})

function saveTemplate() {
	var ajaxMsgSuccess = $('#ajax_msg');
	var ajaxMsgFail    = $('#ajax_msg_fail');
	$.ajax({
		url        : $(this).attr('action'),
		type       : 'post',
		dataType   : 'json',
		data       : $(this).serialize(),
		beforeSend : function() {
			ajaxMsgSuccess.fadeIn();
		},
		success : function(response) {
			if (response.done == true){
				ajaxMsgSuccess.html('Template saved');
				if (response.status == 'new') {
					$(this).find('input').val('');
				}
			} else {
				ajaxMsgSuccess.html(response.errors.join('. '));
			}
			//top.location.reload();
		},
		error: function() {
			ajaxMsgSuccess.hide();
			ajaxMsgFail.show();
		}
	})
	return false;
}

function showTemplateList(data){
	$('#templatelist').html(data).slideDown();
}

function deleteTemplate(templateContainer) {
	$.post(
		$('#website_url').val()+'backend/backend_theme/deletetemplate/',
		{"id": templateContainer.find('input[name="template-id"]').val()},
		function(response) {
			if (response.done == true){
				templateContainer.remove();
			} 
			$('#ajax_msg').text(response.status).fadeIn().delay(5000).fadeOut();
		},
		'json'
	);
}
