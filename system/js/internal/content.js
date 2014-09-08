$(function() {
	$('#tabs').tabs();
    $('#tabs ul').removeClass('ui-corner-all').addClass('ui-corner-top');

    $(document).one('click', 'a[href="#tabs-frag-2"]', function() {
        $('#tabs-frag-2').empty().load($('#website_url').val() + 'backend/backend_content/loadwidgetmaker/');
    });

    $(document).on('click', 'a.ui-tabs-anchor', function() {
        var bigTab = $(this).find('#products, #news');
        if(!bigTab.length) {
            $('.mce-toolbar-grp').show();
            $('.above-editor-links').removeClass('grid_4').addClass('grid_8');
            $('#tabs').removeClass('grid_8').addClass('grid_4');
        } else {
            $('.above-editor-links').removeClass('grid_8').addClass('grid_4');
            $('#tabs').removeClass('grid_4').addClass('grid_8');
            $('.mce-toolbar-grp').hide();
        }
    });

    $(document).on('click', '[aria-label="Fullscreen"]', function() {
        var popup = $(window.parent.document).find('[aria-describedby="toasterPopup"]');
        popup.toggleClass('screen-expand');
        var $tabs = $('#tabs'),
            height = $tabs.height(),
            tabNavHeight = $tabs.find('.ui-tabs-nav').height(),
            $tabHeader = $tabs.find('#adminthingsviewer .ui-accordion-header'),
            tabHeaderLenght = $tabHeader.length,
            tabHeaderHeight = $tabHeader.outerHeight(),
            tabFolderFieldHeight = $tabs.find('#adminselectimgfolder').outerHeight(),
            tabProductButton = $tabs.find('#btn-create').outerHeight(),
            tabNetContentButton = $tabs.find('#widgetSync').outerHeight() + 5;

        $tabs.find('#adminthingsviewer .ui-accordion-content').css({
            'max-height' : height - tabNavHeight - (tabHeaderHeight + 2) * tabHeaderLenght  - tabFolderFieldHeight - 30
        });
        $tabs.find('#product-products').css({
            'height' : height - tabNavHeight - tabProductButton - 116
        });
        $tabs.find('.netcontent-widget-list').css({
            'height' : height - tabNavHeight - tabNetContentButton - 12
        });

    });

    $('#btn-submit').click(function(){
        $('#frm_content').submit();
    });

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
			beforeSend : showSpinner(),
			success : function() {
				localStorage.removeItem(generateStorageKey());
				top.location.reload();
			},
			error: function(response) {
				showMessage(response.responseText, true);
			}
		});
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
					$('#images_small').find('.images-preview').replaceWith(images.small);
					$('#images_medium').find('.images-preview').replaceWith(images.medium);
					$('#images_large').find('.images-preview').replaceWith(images.large);
					$('#images_original').find('.images-preview').replaceWith(images.original);


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
			});
		//}
	});

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
	});

//	$('#toogletinymce').click(function() {
//		var editorId = 'content';
//        $('#tabs').tabs({active : 0}).toggleClass('hidden');
//
//        if($('#tabs.grid_8').length){
//            $('#tabs').toggleClass('grid_4 grid_8');
//            $('.above-editor-links').toggleClass('grid_12 grid_4');
//        }else{
//            $('.above-editor-links').toggleClass('grid_12 grid_8');
//        }
//
//		if(!tinyMCE.getInstanceById(editorId)) {
//			$(this).text('SHOW HTML');
//			tinyMCE.execCommand('mceAddControl', false, editorId);
//		}
//		else {
//			$(this).text('SHOW EDITOR');
//			tinyMCE.execCommand('mceRemoveControl', false, editorId);
//
//		}
//	});

	var restoredContent = localStorage.getItem(generateStorageKey());
	if(restoredContent !== null) {
		showConfirm('We have found content that has not been saved! Restore?', function() {
            tinymce.activeEditor.setContent(restoredContent);
			$('#content').val(restoredContent);
		}, function() {
			localStorage.removeItem(generateStorageKey());
		}, 'success');
	}
});

function dispatchEditorKeyup(editor, event, keyTime) {
    var keyTimer = keyTime;
    if(keyTimer === null) {
        keyTimer = setTimeout(function() {
		    localStorage.setItem(generateStorageKey(), tinymce.activeEditor.getContent());
            keyTimer = null;
	    }, 1000)
    }
}

function insertFileLink(fileName) {
    tinymce.activeEditor.execCommand(
		'mceInsertContent',
		false,
		'<a href="' + $('#website_url').val() + 'media/' + $('#adminselectimgfolder').val() + '/' + fileName + '" title="' + fileName + '">' + fileName + '</a>'
	);
}
