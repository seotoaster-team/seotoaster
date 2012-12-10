$(function() {
	$('#tabs').tabs();

	$('#frm_content').submit(function() {
		var elements = {
			content       : $(this).find('#content').val(),
			containerType : $(this).find('#container_type').val(),
			containerName : $(this).find('#container_name').val(),
			pageId        : $(this).find('#page_id').val(),
			containerId   : $(this).find('#container_id').val(),
			published     : ($('#published').prop('checked')) ? 1 : 0,
			publishOn     : $('#datepicker').val()
		};
		$.ajax({
			url        : $(this).attr('action'),
			type       : 'post',
			dataType   : 'json',
			data       : elements,
			beforeSend : function() {
				showSpinner();
			},
			success : function() {
				localStorage.removeItem(generateStorageKey());

				top.location.reload();
			},
			error: function(response) {
				showMessage(response.responseText(), true);
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

	$(document).on('click', '#files', function() {
		var listFiles = $('#list_files');
		//if(!listFiles.html().length) {
			$.ajax({
				url        : $('#website_url').val() + 'backend/backend_content/loadfiles',
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
				url        : $('#website_url').val() + 'backend/backend_content/loadwidgetmaker',
				type       : 'post',
				dataType   : 'json',
				success : function(response) {
					widgetsMaker.html(response.responseText);
				},
				error: function() {
					widgetsMaker.html('<h4 style="text-align: center;">Unable to load widget maker.</h4>');
				}
			})
		}
	})

	$('#toogletinymce').click(function() {
		var editorId = 'content';
		if(!tinyMCE.getInstanceById(editorId)) {
			$(this).text('NO EDITOR');
			tinyMCE.execCommand('mceAddControl', false, editorId);
		}
		else {
			$(this).text('WITH EDITOR');
			tinyMCE.execCommand('mceRemoveControl', false, editorId);
		}
	});

	var restoredContent = localStorage.getItem(generateStorageKey());
	if(restoredContent !== null) {
		showConfirm('We have found content that has not been saved! Restore?', function() {
			$('#content').val(restoredContent);
		}, function() {
			localStorage.removeItem(generateStorageKey());
		}, 'success');
	}
})

function dispatchEditorKeyup(editor, event) {
    if(editor.keyUpTimer === null) {
	    editor.keyUpTimer = setTimeout(function() {
		    localStorage.setItem(generateStorageKey(), editor.getContent());
		    editor.keyUpTimer = null;
	    }, 1000)
    }
}

function insertFileLink(fileName) {
	$('#content').tinymce().execCommand(
		'mceInsertContent',
		false,
		'<a href="/media/' + $('#adminselectimgfolder').val() + '/' + fileName + '" title="' + fileName + '">' + fileName + '</a>'
	);
}
