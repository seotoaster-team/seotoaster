$(function() {

	loadFaList();

	$('.add-page').live('click', function() {
		if($(this).attr('checked')) {
			var pageId = $('#pid').val();
			$.post(
				$('#website_url').val() + 'backend/backend_featured/addpagetofa/', {
				pid  : pageId,
				faid : $(this).attr('id')
			},
			function() {
				$('#ajax_msg').text('Added').fadeOut();
				//$('#ajax_msg').text(response.responseText).fadeOut();
			})
		}
	})

});

loadFaList = function() {
	$.getJSON($('#website_url').val() + 'backend/backend_featured/loadfalist/', function(response) {
		$('#fa-list').html(response.faList);
	})
}