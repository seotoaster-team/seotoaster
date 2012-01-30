$(function() {

	$('#urlType-label').hide();

	$('#deeplink-massdel-run').button();

	loadDeeplinksList();

	var urlDropDown = $('#url');
	var urlLabel    = $('#url-label').find('label');
	$('#urlType-0').click(function() {
		$('#url').replaceWith(urlDropDown);
	})
	$('#urlType-1').click(function(){
		$('#url').replaceWith('<input type="text" id="url" name="url" value="http://" />');
	})

	$('#chk-all').click(function() {
		 $('.deeplink-massdel').attr('checked', ($(this).attr('checked')) ? true : false);
	})

	 $('.deeplink-massdel').on('click', function() {
		if(!$('.deeplink-massdel').not(':checked').length) {
			$('#chk-all').attr('checked', true);
		}
		else {
			$('#chk-all').attr('checked', false);
		}
	 })

	$('#deeplink-massdel-run').click(function() {
		var ids = [];
		$('.deeplink-massdel:checked').each(function() {
			ids.push($(this).attr('id'));
		});
		if(!ids.length) {
			showMessage('Select at least one item please', true);
			return false;
		}
		smoke.confirm('You are about to remove a deeplink(s). Are you sure?', function(e) {
			if(e) {
				var url      = $('#website_url').val() + 'backend/backend_seo/removedeeplink/';
				var callback = $('#frm-deeplinks').data('callback');
				showSpinner();
				$.post(url, {id : ids}, function(response) {
					hideSpinner();
					var responseText = (response.hasOwnProperty(responseText)) ? response.responseText : 'Removed.';
					showMessage(responseText, (!(typeof response.error == 'undefined' || !response.error)));
	                if(typeof callback != 'undefined') {
						eval(callback + '()');
					}
				})
			} else {
				$('.smoke-base').remove();
			}
		}, {classname:"errors", 'ok':'Yes', 'cancel':'No'});
	});
})

function loadDeeplinksList() {
	$.getJSON($('#website_url').val() + 'backend/backend_seo/loaddeeplinkslist/', function(response) {
		$('#deeplinks-list').html(response.deeplinksList);
	})
}