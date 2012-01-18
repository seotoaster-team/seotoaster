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

		var messageScreen = $('<div class="info-message"></div>').css({color:'lavender'}).html('Do you really want to remove selected deeplinks?');
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
		}).parent().css({background: 'indianred'});
	})
})

function loadDeeplinksList() {
	$.getJSON($('#website_url').val() + 'backend/backend_seo/loaddeeplinkslist/', function(response) {
		$('#deeplinks-list').html(response.deeplinksList);
	})
}