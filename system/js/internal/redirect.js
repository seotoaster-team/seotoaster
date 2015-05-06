$(function() {
	$('#urlType-label, #to-url-label').hide();
	reloadRedirectsList();
	var toUrlDropDown = $('#to-url');
	$('#urlType').click(function() {
		if($(this).prop('checked')){
			$('#to-url').replaceWith(toUrlDropDown);
		}else{
			$('#to-url').replaceWith('<input type="text" id="to-url" name="toUrl" value="http://" />');
		}
	});

	$('.redirect-massdel').on('click', function() {
		if(!$('.redirect-massdel').not(':checked').length) {
			$('#massdell-main').attr('checked', true);
		}
		else {
			$('#massdell-main').attr('checked', false);
		}
	});

	$('#massdell-main').click(function() {
		$('.redirect-massdel').prop('checked', ($(this).prop('checked')) ? true : false);
	});

	$('#massdel-run').click(function() {
		var ids = [];
		$('.redirect-massdel:checked').each(function() {
			ids.push($(this).attr('id'));
		});
		if(!ids.length) {
			showMessage('Select at least one item, please', true);
			return false;
		}
		showConfirm('You are about to remove one or many redirects. Are you sure?', function() {
			var callback = $('#frm-redirects').data('callback');
			$.ajax({
				url: $('#website_url').val() + 'backend/backend_seo/removeredirect/id/'+ids.join(','),
				type: 'DELETE',
				dataType: 'json',
				beforeSend: function() {showSpinner();},
				success: function(response) {
					hideSpinner();
					showMessage(response.responseText, response.error);
					if(typeof callback != 'undefined') {
						eval(callback + '()');
					}
				}
			});
		});
	})
});

//callback function for the ajax forms
function reloadRedirectsList() {
	$('input:text').val('http://');
	showSpinner();
	$.getJSON($('#website_url').val() + 'backend/backend_seo/loadredirectslist/', function(response) {
		hideSpinner();
		$('#redirects-list').html(response.redirectsList);
		checkboxRadioStyle();
	});
}
