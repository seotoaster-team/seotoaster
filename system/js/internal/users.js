$(function() {
	userCallback();
    $('#export-users').click(function() {
        $('#expusrs').submit();
    })
});

function userCallback() {
	showSpinner();
	$.getJSON($('#website_url').val() + 'backend/backend_user/list/', function(response) {
		hideSpinner();
		$('.sortable').dataTable({
			"bPaginate"     : false,
			"bLengthChange" : true,
			"bInfo"         : false,
			"bAutoWidth"    : false,
			"bDestroy"      : true,
			"bRetrive"      : true,
			"bProcessing"   : false
		})
		$('#users-list tbody').html(response.usersList);
	})
}