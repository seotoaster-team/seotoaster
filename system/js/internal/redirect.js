$(function() {
	$('#urlType-label, #to-url-label').hide();


	$('#massdel-run').button();

	reloadRedirectsList();

	var toUrlDropDown = $('#to-url');
	$('#urlType-0').click(function() {
		$('#to-url').replaceWith(toUrlDropDown);
	})
	$('#urlType-1').click(function(){
		$('#to-url').replaceWith('<input type="text" id="to-url" name="toUrl" value="http://" />');
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
		var messageScreen = $('<div class="info-message error"></div>').html('Do you really want to remove selected redirects?').css({background: 'indianred', color: 'lavender'});
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
		}).parent().css({background: 'indianred'});
	})
})

//callback function for the ajax forms
function reloadRedirectsList() {
	$('input:text').val('http://');
	$.getJSON($('#website_url').val() + 'backend/backend_seo/loadredirectslist/', function(response) {
		$('#redirects-list').html(response.redirectsList);
	})
}
