$(function() {

	$('#deeplink-massdel-run').button();

	loadDeeplinksList();

	var urlDropDown = $('#url');
	var urlLabel    = $('#url-label').find('label');
	$('#domain-toggle-deeplinks').toggle(function(){
		$(this).text('Internal?');
		urlLabel.text('Type url');
		$('#nofollow').attr('checked', true);
		$('#url').replaceWith('<input type="text" id="url" name="url" value="http://" />');
	},
	function() {
		$(this).text('External?');
		urlLabel.text('Select page');
		$('#nofollow').attr('checked', false);
		$('#url').replaceWith(urlDropDown);
	})


	$('#chk-all').click(function() {
		 $('.deeplink-massdel').attr('checked', ($(this).attr('checked')) ? true : false);
	})

	 $('.deeplink-massdel').live('click', function() {
		if(!$('.deeplink-massdel').not(':checked').length) {
			$('#chk-all').attr('checked', true);
		}
		else {
			$('#chk-all').attr('checked', false);
		}
	 })

	$('#deeplink-massdel-run').click(function() {

		var messageScreen = $('<div class="info-message"></div>').html('Do you really want to remove selected deeplinks?');
		$(messageScreen).dialog({
			modal    : true,
			title    : 'Removing deeplinks?',
			resizable: false,
			buttons: {
				Yes: function() {
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
					$('#chk-all').attr('checked', false);
					$(this).dialog('close');
				},
				No : function() {
					$(this).dialog('close');
				}
			}
		});
	})
})

function loadDeeplinksList() {
	$.getJSON($('#website_url').val() + 'backend/backend_seo/loaddeeplinkslist/', function(response) {
		$('#deeplinks-list').html(response.deeplinksList);
	})
}