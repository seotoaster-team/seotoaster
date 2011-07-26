$(function() {

	$('#deeplink-massdel-run').button();

	loadDeeplinksList();

	var urlDropDown = $('#url');
	var urlLabel    = $('#url-label').find('label');
	$('#domain-toggle-deeplinks').toggle(function(){
		$(this).text('Internal?');
		urlLabel.text('Type url');
		$('#url').replaceWith('<input type="text" id="url" name="url" value="" placeholder="Type external url here..."/>');
	},
	function() {
		$(this).text('External?');
		urlLabel.text('Select page');
		$('#url').replaceWith(urlDropDown);
	})

	$('#deeplink-massdel-run').click(function() {
		var ids = [];
		$('.deeplink-massdel:checked').each(function() {
			ids.push($(this).attr('id'));
		});
		var url      = $('#website_url').val() + 'backend/backend_seo/removedeeplink/'
		var callback = $('#frm-deeplinks').data('callback');
		$.post(url, {id: ids}, function() {
			if(callback) {
				eval(callback + '()');
			}
			$('#ajax_msg').text('Done').fadeOut();
		})
	})
})

function loadDeeplinksList() {
	$.getJSON($('#website_url').val() + 'backend/backend_seo/loaddeeplinkslist/', function(response) {
		$('#deeplinks-list').html(response.deeplinksList);
	})
}