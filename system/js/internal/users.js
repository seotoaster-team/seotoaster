$(function() {
	userCallback();
	$('.sortable').dataTable({
		"bPaginate": false,
		"bLengthChange": false,
		"bInfo": false,
		"bAutoWidth": false,
		"bDestroy":true,
		"bRetrive" : true
	});

    $('#export-users').click(function() {
        $('#expusrs').submit();
    })
});

function userCallback() {
	showSpinner();
	$.getJSON($('#website_url').val() + 'backend/backend_user/list/', function(response) {
		hideSpinner();
		$('#users-list tbody').html(response.usersList);
	})
}