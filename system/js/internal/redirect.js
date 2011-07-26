$(function() {

	$('#massdel-run').button();

	reloadRedirectsList();

	var toUrlDropDown = $('#to-url');
	$('#domain-toggle').toggle(function(){
		$(this).text('Local url?');
		$('#to-url-label').find('label').text('Extarnal url');
		$('#to-url').replaceWith('<input type="text" id="to-url" name="toUrl" value="" placeholder="Type external url here..."/>');
	},
	function() {
		$(this).text('Extarnal url?');
		$('#to-url-label').find('label').text('Local url');
		$('#to-url').replaceWith(toUrlDropDown);
	})

	$('.redirect-massdel').live('click', function() {
		var parentRow = $(this).parent().parent();
		if($(this).attr('checked')) {
			parentRow.css({
				opacity: '0.6'
			})
		}
		else {
			parentRow.css({
				opacity: '1'
			})
		}
	})

	$('#massdell-main').click(function() {
		if($(this).attr('checked')) {
			$('.redirect-massdel').attr('checked', true);
		}
		else {
			$('.redirect-massdel').attr('checked', false);
		}
	})

	$('#massdel-run').click(function() {
		var ids = [];
		$('.redirect-massdel:checked').each(function() {
			ids.push($(this).attr('id'));
		});
		var url      = $('#website_url').val() + 'backend/backend_seo/removeredirect/'
		var callback = $('#frm-redirects').data('callback');
		$.post(url, {id: ids}, function() {
			if(callback) {
				eval(callback + '()');
			}
			$('#ajax_msg').text('Done').fadeOut();
		})
	})
})

//callback function for the ajax forms
function reloadRedirectsList() {
	$.getJSON($('#website_url').val() + 'backend/backend_seo/loadredirectslist/', function(response) {
		$('#redirects-list').html(response.redirectsList);
	})
}
