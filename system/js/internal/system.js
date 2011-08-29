$(function() {
	_FADE_SLOW = 5000;

//	if(jQuery().chosen) {
//		$('._tdropdown').chosen();
//	}

	/**
	 * Seotoaster popup dialog
	 */
	$('a.tpopup').click(function(e) {
		e.preventDefault();
		link    = $(this);
		pwidth  = link.data('pwidth') || 960;
		pheight = link.data('pheight') || 650;
		if(top.$('#__tpopup').length) {
			top.$('#__tpopup').dialog('option', {
				width: pwidth,
				height: pheight
			});
			top.$('#__tpopup').attr('src', link.data('url')).css({
				width    : pwidth + 'px',
				height   : pheight + 'px'
			});
			return;
		}
		popup = $(document.createElement('iframe')).attr('id', '__tpopup');
		popup.dialog({
			width: pwidth,
			height: pheight,
			resizable : false,
			modal: true,
			open: function() {
				$(this).attr('src', link.data('url')).css({
						width    : pwidth + 'px',
						height   : pheight + 'px',
						padding  : '0px',
						margin   : '0px',
						overflow : 'hidden'
				});
				$('.ui-dialog-titlebar').hide();
			},
			close: function() {
				$(this).remove();
			}
		});
	});

	$('a._tdelete').live('click', function() {
		$('#ajax_msg').text('Removing...').show();
		var url = $(this).attr('href');
		if(!url || url == 'undefined' || url == 'javascript:;') {
			url = $(this).data('url');
		}
		var callback = $(this).data('callback');
		var deleteScreen = document.createElement('div');
		$(deleteScreen).html('<h2>Are you sure?</h2>');
		var link = $(this);
		$(deleteScreen).dialog({
			modal: true,
			title: 'Delete',
			resizable: false,
			buttons: {
				'Delete': function() {
					$.post(url, {id: link.data('eid')}, function(response) {
						$('#ajax_msg').text(response.responseText);
						if(response.error){
							$('#ajax_msg').addClass('ui-state-error').show();
						}
						else {
							$('#ajax_msg').removeClass('ui-state-error').fadeOut();
						}
						if(callback) {
							eval(callback + '()');
						}
						$(deleteScreen).dialog( "close" );
					})
				},
				Cancel: function() {
					$('#ajax_msg').text('').hide();
					$( this ).dialog( "close" );
				}
			}
		});
	})

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
						if(typeof response.responseText.redirectTo != 'undefined') {
							top.location.href = $('#website_url').val() + response.responseText.redirectTo;
							return;
						}
						top.location.reload();
						return;
					}

					//processing callback
					var callback = $(form).data('callback');
					if(typeof callback != 'undefined') {
						eval(callback + '()');
					}

					ajaxMessage.html(response.responseText).fadeOut(_FADE_SLOW);
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

	$('.lang-select').live('click', function() {
		var language = $(this).attr('id');
		$.post( $('#website_url').val() + 'language', {lng: language}, function() {
			window.location.reload();
		})
	})

	$('a._tedit').live('click', function(e) {
		e.preventDefault();

		var handleUrl = $(this).data('url');
		if(!handleUrl || handleUrl == 'undefined') {
			handleUrl = $(this).attr('href');
		}

		var eid = $(this).data('eid');

		$.post(handleUrl, {id: eid}, function(response) {
			//console.log(response.responseText.data);
			var formToLoad = $('#' + response.responseText.formId);
			for(var i in response.responseText.data) {
				$('[name=' + i + ']').val(response.responseText.data[i]);
				if(i == 'password') {
					$('[name=' + i + ']').val('');
				}
			}
		})

	})
});