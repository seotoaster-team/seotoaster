/**
 *  Init script for jQuery-File-Upload
 *  @controller: backend_theme
 *  @action: themes 
 */
$(function () {
	var maxFiles = 1; //doesn't matter for now
	var maxFileSize = $('#MAX_FILE_SIZE').val();
	var filesCounter = 0;

	$('#toaster-uploader').fileUploadUI({
		uploadTable: $('#theme-upload-status'),
		dragDropSupport: false,
		buildUploadRow: function (files, index) {
            return $('<tr><td>' + files[index].name + '<\/td>' +
                    '<td class="file_upload_progress"><div><\/div><\/td>' +
                    '<td class="file_upload_cancel">' +
                    '<button class="ui-state-default ui-corner-all" title="Cancel">' +
                    '<span class="ui-icon ui-icon-cancel">Cancel<\/span>' +
                    '<\/button><\/td><\/tr>');
        },
		buildDownloadRow: function (file, handler) {
			if (handler.response.error !== false ){
				return $('<tr class="error"><td><b>' + file.name + '&nbsp;</b><\/td><td>'+handler.response.error+'<\/td><\/tr>');
			}
			return $('<tr><td>Theme "<b>' + file.themename + '</b>" installed successfully<\/td><\/tr>');
		},
		beforeSend: function (event, files, index, xhr, handler, callBack) {
			// prevent empty files uploading 
			if (files[index].size === 0) {
				handler.uploadRow.find('.file_upload_progress').html('FILE IS EMPTY!');
				setTimeout(function () {
					handler.removeNode(handler.uploadRow);
				}, 3000);
				return;
			}
			// prevent uploading file more then max allowed size
			if (files[index].size > maxFileSize) {
				handler.uploadRow.find('.file_upload_progress').html('FILE TOO BIG!');
				setTimeout(function () {
					handler.removeNode(handler.uploadRow);
				}, 3000);
				return;
			}
			// prevent uploading files that not match mime-type
			if (files[index].type !== 'application/zip') {
				handler.uploadRow.find('.file_upload_progress').html('NOT A ZIP FILE!');
				setTimeout(function () {
					handler.removeNode(handler.uploadRow);
				}, 3000);
				return;
			}
			callBack();
		},
		onCompleteAll: function (list) {
			$('#themes-list').trigger('updateContent');
		},
		onError: function (event, files, index, xhr, handler) {
			// For JSON parsing errors, the load event is saved as handler.originalEvent:
			if (handler.originalEvent) {
				/* handle JSON parsing errors ... */
				alert('JSON parsing errors');
			} else {
				/* handle XHR upload errors ... */
				alert('XHR upload errors');	
			}
			handler.uploadRow.find('.file_upload_progress').html('ERROR');
			handler.uploadRow.find('.file_upload_cancel').remove();
			setTimeout(function () {
				handler.removeNode(handler.uploadRow);
			}, 3000);
		}
	});
});

