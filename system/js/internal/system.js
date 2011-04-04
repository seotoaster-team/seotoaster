$(function() {
	var wndDialog = $( "#seotoaster_popup_dialog" )

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
		//console.log($(this).attr('url'));
	});

});
