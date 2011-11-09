$(function() {
	$('#addFeaturedArea-label').hide();

	loadFaList();
	$('.add-page').live('click', function() {

		var chckbx = $('[type=checkbox]:checked');

		window.parent.jQuery('a.featured').text((chckbx.length) ? 'Yes, ' + chckbx.length + ' times' : 'Not yet');

		var pageId     = $('#pid').val();
		var faId       = $(this).attr('id');
		var pcountEl   = $('.pcount-' + faId);
		var handlerUrl = ($(this).prop('checked')) ? 'addpagetofa/' : 'rempagefromfa/'
		var el         = $(this);
		$.post(
			$('#website_url').val() + 'backend/backend_featured/' + handlerUrl, {
			pid  : pageId,
			faid :faId
		},
		function(response) {
			$('#ajax_msg').html(response.responseText).fadeIn('slow').fadeOut('slow');
			pcountEl.text((el.prop('checked') ? parseInt(pcountEl.text()) + 1 : parseInt(pcountEl.text()) - 1)) ;
		})
	})

});

function loadFaList() {
	$.getJSON($('#website_url').val() + 'backend/backend_featured/loadfalist/pid/' + $('#pid').val() , function(response) {
		$('#fa-list').html(response.faList);
	})
}