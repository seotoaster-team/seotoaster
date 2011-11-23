$(function() {
	userCallback();
})

function userCallback() {
	$.getJSON($('#website_url').val() + 'backend/backend_user/list/', function(response) {
		//$('#users-list').html(response.usersList);
		$('#users-list tbody').html(response.usersList);
		$('.sortable').dataTable({
			"bPaginate": false,
			"bLengthChange": false,
			"bInfo": false,
			"bAutoWidth": false
		});
	})
}