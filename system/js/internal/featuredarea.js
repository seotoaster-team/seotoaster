$(function() {
	$('#addFeaturedArea-label').hide();

	loadFaList();
	$(document).on('click', 'input.add-page', function() {
        showSpinner("#fa-list");
		var chckbx = $('#fa-list [type=checkbox]:checked');

		$('.featured-link').html((chckbx.length) ? '<span class="ticon-tags icon14"></span> Page was added ' + chckbx.length + ' times in <a class="featured" href="javascript:;" title="tags">tags</a>' : '<span class="ticon-tags icon14"></span> <a class="featured" href="javascript:;" title="tags">Tag this page</a>');

		var pageId     = $('#pageId').val();
		var faId       = $(this).attr('id');
		var pcountEl   = $('.pcount-' + faId);
		var handlerUrl = ($(this).prop('checked')) ? 'addpagetofa/' : 'rempagefromfa/'
		var el         = $(this);
		$.post(
			$('#website_url').val() + 'backend/backend_featured/' + handlerUrl, {
			pid  : pageId,
			faid :faId,
            secureToken: $('#frm-page').find('.secureToken').val()
		},
		function(response) {
			hideSpinner();
			showMessage(response.responseText);
			//$('#ajax_msg').html(response.responseText).fadeIn('slow').fadeOut('slow');
			pcountEl.text((el.prop('checked') ? parseInt(pcountEl.text()) + 1 : parseInt(pcountEl.text()) - 1)) ;
		})
	});

});

function loadFaList() {
	$.getJSON($('#website_url').val() + 'backend/backend_featured/loadfalist/pid/' + $('#pageId').val() , function(response) {
		$('#fa-list').html(response.faList);
		$('#fa-name').val('');
		checkboxRadioStyle();
	});
}