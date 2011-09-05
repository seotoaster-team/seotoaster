$(function() {
	$('#tabs').tabs();
	$('#dpkr').css({width : '250px'}).datepicker();

	if($('#published').length) {pubunpub();}
	$('#published').live('click', pubunpub);

	$('#frm_content').submit(function() {
		var ajaxMsgSuccess = $('#ajax_msg');
		var ajaxMsgFail    = $('#ajax_msg_fail');
		elements = {
			content       : $(this).find('#content').val(),
			containerType : $(this).find('#container_type').val(),
			containerName : $(this).find('#container_name').val(),
			pageId        : $(this).find('#page_id').val(),
			containerId   : $(this).find('#container_id').val(),
			published     : ($('#published').attr('checked')) ? 1 : 0,
			publishOn     : $('#dpkr').val()
		}
		$.ajax({
			url        : $(this).attr('action'),
			type       : 'post',
			dataType   : 'json',
			data       : elements,
			beforeSend : function() {
				ajaxMsgSuccess.fadeIn();
			},
			success : function() {
				top.location.reload();
			},
			error: function() {
				ajaxMsgSuccess.hide();
				ajaxMsgFail.show();
			}
		})
		return false;
	});

	$('#adminselectimgfolder').change(function(){
		var selectedFolder = $(this).val();
		if(selectedFolder && selectedFolder != 0) {
			$.ajax({
				url        : $('#website_url').val() + 'backend/backend_content/loadimages',
				type       : 'post',
				dataType   : 'json',
				data       : {
					folderName: selectedFolder
				},
				beforeSend : function() {
					//console.log('loading...');
				},
				success : function(images) {
					$('#images_small').html(images.small);
					$('#images_medium').html(images.medium);
					$('#images_large').html(images.large);
					$('#images_original').html(images.original);
				},
				error: function() {
					//console.log('error');
				}
			})
		}
	});

	$('#files').click(function(){
		var listFiles = $('#list_files');
		//if(!listFiles.html().length) {
			$.ajax({
				url        : '/backend/backend_content/loadfiles',
				type       : 'post',
				dataType   : 'json',
				data       : {
					folder : $('#adminselectimgfolder').val()
				},
				success : function(response) {
					listFiles.html(response.html);
				},
				error: function() {
					listFiles.html('Unable to load files list');
				}
			})
		//}
	})

	$('#widgets').click(function(){
		var widgetsMaker = $('#widgets_maker');
		if(!widgetsMaker.html().length) {
			$.ajax({
				url        : '/backend/backend_content/loadwidgetmaker',
				type       : 'post',
				dataType   : 'json',
				success : function(response) {
					widgetsMaker.html(response.responseText);
				},
				error: function() {
					widgetsMaker.html('Unable to load widget maker.');
				}
			})
		}
	})

	$('#toogletinymce').click(function() {
		var editorId = 'content';
		if(!tinyMCE.getInstanceById(editorId)) {
			tinyMCE.execCommand('mceAddControl', false, editorId);
		}
		else {
			tinyMCE.execCommand('mceRemoveControl', false, editorId);
		}
	})
})


function insertFileLink(fileName) {
	$('#content').tinymce().execCommand(
		'mceInsertContent',
		false,
		'<a href="/media/' + $('#adminselectimgfolder').val() + '/' + fileName + '" title="' + fileName + '">' + fileName + '</a>'
	);
}

function pubunpub() {
	var chckbxPublished = $('#published');
	var dpkr            = $('#dpkr');
	var published = (chckbxPublished.attr('checked')) ? true : false;
	dpkr.attr('disabled', published);
	if(published) {
		dpkr.val('');
	}
}
