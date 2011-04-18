$(function() {
	var wndDialog = $("#seotoaster_popup_dialog");
	$('a.tpopup').click(function(e) {
		e.preventDefault();
		url = $(this).attr('url');
		wndDialog.dialog({
			width    : 960,
			height   : 600,
			modal    : true,
			autoOpen : false,
			resizable: false,
			title    : $(this).attr('title'),
			open: function() {
				$.get(url, function(response) {
					response += '<div class="clear"></div>';
					wndDialog.append(response);
				});
			},
			close: function() {
				wndDialog.html('');
				wndDialog.dialog('destroy');
			}
		});
		wndDialog.dialog('open');
	});

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
				ajaxMessage.html('Working...').fadeIn();
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