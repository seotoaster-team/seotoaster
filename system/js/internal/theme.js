window.onload = function() {
    var editor = ace.edit("edittemplate");
        editor.setTheme("ace/theme/crimson_editor");
    var HTMLMode = require("ace/mode/html").Mode;
        editor.getSession().setMode(new HTMLMode());
        editor.getSession().getValue();
        editor.getSession().setUseWrapMode(true);
$(function() {
	$('#frm_template').submit(saveTemplate);

	$('#templatelist').delegate('div.template_preview', 'click', function(){
		$.post(
			$('#website_url').val()+'backend/backend_theme/gettemplate/',
			{'listtemplates': $(this).find('input[name="template-id"]').val()},
			function(response){
				if (response.error != false){
					$('#frm_template').find('#title').val(response.responseText.name);
                    editor.getSession().setValue(response.responseText.content);
					//$('#frm_template').find('#template-content').val(response.responseText.content);
					$('#frm_template').find('#template_id').val(response.responseText.name);
					$('#frm_template').find('#template-type').val(response.responseText.type);
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
	});

	$('#listtemplates-btn').button().click(function(){
		$.post(
			$('#website_url').val()+'backend/backend_theme/gettemplate/',
			{'listtemplates':'all', 'pageId' : $('#pageId').val()},
			function(html){
				$('#templatelist').html(html).slideDown().css('overflow-y', 'auto');
			},
			'html');
	});
});

function saveTemplate() {
	var ajaxMsg = $('#ajax_msg');
    var templateContent = editor.getSession().getValue();
	$.ajax({
		url        : $(this).attr('action'),
		type       : 'post',
		dataType   : 'json',
		data : {
            content : templateContent,
            pageId : $('#pageId').val(),
            templateType : $('#template-type').val(),
            name : $('#title').val(),
            id: $('#template_id').val()
        },

		beforeSend : function() {
			ajaxMsg.fadeIn().text('Working...');
		},
		success : function(response) {
			if (response.error != true){
				ajaxMsg.text('Template saved').fadeOut(_FADE_FAST);
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
}
