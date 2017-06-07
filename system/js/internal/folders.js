$(function() {
	//$('#urlType-label, #to-url-label').hide();
	reloadPageFolders();
	var indexDropDown = $('#indexPage');
	$('#indexPage').on('click', function() {
		$('#indexPage').replaceWith(indexDropDown);
	});
});

//callback function for the ajax forms
function reloadPageFolders() {
	$('input:text').val('');
	showSpinner();
	$.getJSON($('#website_url').val() + 'backend/backend_page/loadpagefolders/', function(response) {
		hideSpinner();
		$('#folders-list').html(response.pagefolders);
		checkboxRadioStyle();
	});
}
