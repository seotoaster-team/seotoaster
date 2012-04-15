$(function() {
	userCallback();
	$('#export-users').click(function() {
        $('#expusrs').submit();
    })
});

function userCallback() {
	$.getJSON($('#website_url').val() + 'backend/backend_user/list/', function(response) {
		hideSpinner();
		$('#users-list tbody').html(response.usersList);
		var tblContainer = $('.sortable');
		if(!tblContainer.hasClass('dataTable')) {
			tblContainer.dataTable({
				"bInfo"         : false,
				"bPaginate"     : false
			});
		}
	})
}