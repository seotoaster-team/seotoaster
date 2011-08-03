$(function() {
	userCallback();
})

function userCallback() {
	$.getJSON($('#website_url').val() + 'backend/backend_user/list/', function(response) {
		$('#users-list').html(response.usersList);
	})
}