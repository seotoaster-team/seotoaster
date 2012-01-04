window.onload = function() {
    if (!$.browser.msie) {
        var editor = ace.edit("edittemplate");
        editor.setTheme("ace/theme/crimson_editor");
        var HTMLMode = require("ace/mode/html").Mode;
		$('#title').focus();
        editor.getSession().setMode(new HTMLMode());
        editor.getSession().getValue();
        editor.getSession().setUseWrapMode(true);
		editor.setShowPrintMargin(false);
    }
$(function() {
	$('#frm_template').submit(saveTemplate);

	$('#templatelist').delegate('div.template_preview', 'click', function(){
		$.post(
			$('#website_url').val()+'backend/backend_theme/gettemplate/',
			{'listtemplates': $(this).find('input[name="template-id"]').val()},
			function(response){
				if (response.error != false){
					$('#frm_template').find('#title').val(response.responseText.name);
                    $.browser.msie ? $('#frm_template').find('#template-content').val(response.responseText.content) : editor.getSession().setValue(response.responseText.content);
					$('#frm_template').find('#template_id').val(response.responseText.name);
					$('#frm_template').find('#template-type').val(response.responseText.type);
					$('#template_preview').attr('src', $('#website_url').val()+response.responseText.preview);
				}
			},
			'json'
		);
		$('#templatelist').slideUp();
	}).delegate('div.template_delete', 'click', function(){
		deleteTemplate($(this).closest('div.template_item'));
		return false;
	});

	$('#listtemplates-btn').click(function(e){
		e.preventDefault();
		$.post(
			$('#website_url').val()+'backend/backend_theme/gettemplate/',
			{'listtemplates':'all', 'pageId' : $('#pageId').val()},
			function(html){
				$('#templatelist').html(html).slideDown().css('overflow-y', 'auto');
			},
			'html');
	});

	$('textarea').keydown(function(e) {
		if(e.ctrlKey && e.keyCode == 83) {
			e.preventDefault();
			saveTemplate();
		}
	})
});

function saveTemplate() {
	var ajaxMsg = $('#ajax_msg');
    if (!$.browser.msie){
        var templateContent = editor.getSession().getValue();
    }
	$.ajax({
		url        : $(this).attr('action'),
		type       : 'post',
		dataType   : 'json',
		data: $.browser.msie ? $(this).serialize() : {
            content : templateContent,
            pageId : $('#pageId').val(),
            templateType : $('#template-type').val(),
            name : $('#title').val(),
            id: $('#template_id').val()
        },

		beforeSend : function() {
			//ajaxMsg.fadeIn().text('Working...');
		},
		success : function(response) {
			if (response.error != true) {
				//ajaxMsg.text('Template saved').fadeOut(_FADE_FAST);
				smoke.signal('Template saved');
				$('.smoke-base').delay(1300).slideUp();
				if (response.responseText == 'new') {
					//$(this).find('input').val('');
					$('#title').val('');
					editor.getSession().setValue('');
				}
			} else {
				smoke.alert(response.responseText.join('. '), {ok: 'Okay', classname:'errors'});
				//ajaxMsg.html(response.responseText.join('. '));
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
    var messageScreen = $('<div class="info-message"></div>').css({color:'lavender'}).html('Do you really want to remove this template?');
	$(messageScreen).dialog({
		modal    : true,
		title    : 'Remove template?',
		resizable: false,
		buttons: {
			Yes: function() {
				$.ajax({
					url: $('#website_url').val()+'backend/backend_theme/deletetemplate/',
					type: 'post',
					data: {"id": templateContainer.find('input[name="template-id"]').val()},
					success: function(response) {
						if (response.error == false){
							templateContainer.remove();
						}
						//$('#ajax_msg').text(response.responseText).fadeIn().fadeOut(_FADE_FAST);
						smoke.signal(response.responseText);
						$('.smoke-base').fadeOut(_FADE_FAST);
					},
					dataType: 'json'
				});
				$(this).dialog('close');
			},
			No : function() {
				$(this).dialog('close');
			}
		}
	}).parent().css({background: 'indianred'});
}

}

