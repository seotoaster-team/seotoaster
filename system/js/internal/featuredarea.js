$(function() {
	$('#addFeaturedArea-label').hide();

	loadFaList();

	$('.add-page').live('click', function() {
		var pageId   = $('#pid').val();
		var faId     = $(this).attr('id');
		var pcountEl = $('.pcount-' + faId);
		if($(this).attr('checked')) {
			$.post(
				$('#website_url').val() + 'backend/backend_featured/addpagetofa/', {
				pid  : pageId,
				faid :faId
			},
			function(response) {
				$('#ajax_msg').html(response.responseText).fadeIn('slow').fadeOut('slow');
				$(pcountEl).text(parseInt(pcountEl.text()) + 1) ;
			})
		}
		else {
			$.post(
				$('#website_url').val() + 'backend/backend_featured/rempagefromfa/', {
				pid  : pageId,
				faid :faId
			},
			function() {
				$('#ajax_msg').text('Removed').fadeOut();
				$(pcountEl).text(parseInt(pcountEl.text()) - 1) ;
			})
		}
	})

});

loadFaList = function() {
	$.getJSON($('#website_url').val() + 'backend/backend_featured/loadfalist/pid/' + $('#pid').val() , function(response) {
		$('#fa-list').html(response.faList);
	})
}