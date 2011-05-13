$(function() {

	//seotoaster admin panel cookie
	if($.cookie('hideAdminPanel') && $.cookie('hideAdminPanel') === true) {
		$('#cpanelul').hide();
		$('#logoutul').hide()
		$('#seotoaster-logowrap').hide()
	}

	/**
	 * Seotoaster popup dialog
	 */
	$('a.tpopup').click(function(e) {
		e.preventDefault();
		var popupWidth    = 980;
		var popupHeight   = 670;
		var toasterIframe = document.createElement('iframe');
		$(toasterIframe).attr('id', '_toaster-popup')
		var srcUrl        = $(this).attr('url');
		$(toasterIframe).dialog({
			width    : popupWidth,
			minWidth : popupWidth,
			height   : popupHeight,
			modal    : true,
			autoOpen : false,
			resizable: false,
			draggable: true,
			title    : $(this).attr('title'),
			open: function() {
				$(toasterIframe).css({
					width : popupWidth + 'px',
					height: popupHeight + 'px',
					padding: '0px',
					margin: '0px'
				})
				.attr('scrolling', 'no')
				.attr('frameborder', 'no')
				.attr('src', srcUrl);

				$('.ui-dialog-titlebar').hide();
				$('.ui-dialog').css({
					padding: '0px',
					border: '0px',
					background: 'transparent'
				});
			}
		});
		$(toasterIframe).dialog('open');
	});

	/**
	 * close button functionality.
	 *
	 */
	$('.closebutton').click(function() {
		parent.closePopup();
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
				ajaxMessage.fadeIn();
				if(form.hasClass('_reload')) {
					top.location.reload();
				}
			},
			success : function(response) {
				ajaxMessage.html('Saved').fadeOut('slow');
			},
			error: function() {
				ajaxMessage.html('Error occured');
			}
		})
	})
});

function closePopup() {
	$( ".ui-dialog #_toaster-popup" ).dialog('close');
}