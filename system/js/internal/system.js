$(function() {

	/**
	 * Seotoaster popup dialog
	 */
	$('a.tpopup').click(function(e) {
		e.preventDefault();
		var popup = document.createElement('iframe');
		$(popup)
			.attr('src', $(this).attr('url'))
			.attr('scrolling', 'no')
			.attr('frameborder', 'no')
			.attr('id', '__tpopup')
			.dialog({
				width     : 960,
				height    : 650,
				resizable : false,
				modal     : true,
				open      : function() {
					$(popup).css({
						width    : '965px',
						height   : '650px',
						padding  : '0px',
						margin   : '0px',
						overflow : 'hidden'
					});
					$('.ui-dialog-titlebar').hide();
				},
				close     : function() {
					$(popup).remove();
				}
			});
	});

	$('.closebutton').click(function() {
		top.$('#__tpopup').dialog('close');
	})

	$('#ajax_msg').click(function(){
		$(this).fadeOut();
	})

	$('form._fajax').live('submit', function(e) {
		e.preventDefault();
		var ajaxMessage = $('#ajax_msg');
		var form        = $(this);
		$.ajax({
			url        : form.attr('action'),
			type       : 'post',
			dataType   : 'json',
			data       : form.serialize(),
			beforeSend : function() {
				ajaxMessage.fadeIn().removeClass('ui-state-error').addClass('success');
				$('#msg-text').text('Working...');
			},
			success : function(response) {
				if(!response.error) {
					if(form.hasClass('_reload')) {
						if(response.responseText.redirectTo != 'undefined') {
							top.location.href = $('#website_url').val() + response.responseText.redirectTo;
							return;
						}
						top.location.reload();
						return;
					}
					//ajaxMessage.html('Saved').fadeOut('slow');
				}
				else {
					ajaxMessage.removeClass('success').addClass('ui-state-error');
					$('#msg-text').replaceWith(response.responseText);
				}
			},
			error: function() {
				ajaxMessage.removeClass('success').addClass('ui-state-error');
				$('#msg-text').text('Error occured');
			}
		})
	})
});