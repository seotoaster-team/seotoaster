$(function() {
	userCallback();
    $('#export-users').button();
    $('#export-users').click(function() {
        $('#expusrs').submit();
        //$.post($('#website_url').val() + 'backend/backend_user/export/', function(response) {
            //$('#ajax_msg').text(response.responseText).fadeIn().fadeOut();
        //})
    })
})

function userCallback() {
	$.getJSON($('#website_url').val() + 'backend/backend_user/list/', function(response) {
		$('#users-list tbody').html(response.usersList);
		$('.sortable').dataTable({
			"bPaginate": false,
			"bLengthChange": false,
			"bInfo": false,
			"bAutoWidth": false
		});
	})
}