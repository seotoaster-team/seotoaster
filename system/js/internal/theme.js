$(function() {
	$('#frm_template').submit(saveTemplate);

	$('#templatelist').delegate('div.template_preview', 'click', function(){
		$.post(
			$('#website_url').val()+'backend/backend_theme/gettemplate/',
			{'listtemplates': $(this).find('input[name="template-id"]').val()},
			function(response){
				if (response.error != false){
					$('#frm_template').find('#title').val(response.responseText.name);
					$('#frm_template').find('#content').val(response.responseText.content);
					$('#frm_template').find('#template_id').val(response.responseText.name);
					$('#template_preview').attr('src', $('#website_url').val()+response.responseText.preview);
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
	$.ajax({
		url: $('#website_url').val()+'backend/backend_theme/deletetemplate/',
		type: 'POST',
		data: {"id": templateContainer.find('input[name="template-id"]').val()},
		success: function(response) {
			if (response.error == false){
				templateContainer.remove();
			} 
			$('#ajax_msg').text(response.responseText).fadeIn().delay(5000).fadeOut();
		},
		dataType: 'json'
	});
}
