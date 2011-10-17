$(function() {

	$('#massdel-run').button();

	reloadRedirectsList();

	var toUrlDropDown = $('#to-url');
	$('#domain-toggle').toggle(function(){
		$(this).text('Local url?');
		$('#to-url-label').find('label').text('External url');
		$('#to-url').replaceWith('<input type="text" id="to-url" name="toUrl" value="http://" />');
	},
	function() {
		$(this).text('Extarnal url?');
		$('#to-url-label').find('label').text('Local url');
		$('#to-url').replaceWith(toUrlDropDown);
	})

	$('.redirect-massdel').live('click', function() {
		if(!$('.redirect-massdel').not(':checked').length) {
			$('#massdell-main').attr('checked', true);
		}
		else {
			$('#massdell-main').attr('checked', false);
		}
	})

	$('#massdell-main').click(function() {
		$('.redirect-massdel').attr('checked', ($(this).attr('checked')) ? true : false);
	})

	$('#massdel-run').click(function() {
		var messageScreen = $('<div class="info-message"></div>').html('Do you really want to remove selected redirects?');
		$(messageScreen).dialog({
			modal    : true,
			title    : 'Removing redirects?',
			resizable: false,
			buttons: {
				Yes: function() {
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
					$('#massdell-main').attr('checked', false);
					$(this).dialog('close');
				},
				No : function() {
					$(this).dialog('close');
				}
			}
		});
	})
})

//callback function for the ajax forms
function reloadRedirectsList() {
	$('input:text').val('http://');
	$.getJSON($('#website_url').val() + 'backend/backend_seo/loadredirectslist/', function(response) {
		$('#redirects-list').html(response.redirectsList);
	})
}
