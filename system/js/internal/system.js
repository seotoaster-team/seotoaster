$(function() {
	_FADE_SLOW = 5000;

	/**
	 * Seotoaster popup dialog
	 */
	$('a.tpopup').click(function(e) {
		if(!loginCheck()) {
			return;
		}
		e.preventDefault();
		var link    = $(this);
		var pwidth  = link.data('pwidth') || 960;
		var pheight = link.data('pheight') || 580;
		var popup = $(document.createElement('iframe')).attr('scrolling', 'no').addClass('__tpopup rounded3px');
		popup.parent().css({background: 'none'});
		popup.dialog({
			width: pwidth,
			height: pheight,
			resizable : false,
			draggable : true,
			modal: true,
			open: function() {
				$(this).attr('src', link.data('url')).css({
						width    : pwidth + 'px',
						height   : pheight + 'px',
						padding  : '0px',
						margin   : '0px',
						overflow : 'hidden'
				});
				$('.ui-dialog-titlebar').remove();
			},
			close: function() {
				$(this).remove();
			}
		});
	});


	//seotoaster delete item link
	$('a._tdelete').live('click', function() {
		$('#ajax_msg').text('Removing...').show();
		var url = $(this).attr('href');
		if(!url || (typeof url == 'undefined') || url == 'javascript:;') {
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
					$.post(url, {id : link.data('eid')}, function(response) {
						$('#ajax_msg').text(response.responseText);
						if(response.error){
							$('#ajax_msg').addClass('error').show();
						}
						else {
							$('#ajax_msg').removeClass('error').fadeOut();
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
		$('.ui-widget-content').css({background : '#eee'}).addClass('ui-corner-all');
	})

	//seotoaster close popup window button
	$('.closebutton').click(function() {
		window.parent.jQuery('.__tpopup').dialog('close');
	})

	//seotoaster ajax form submiting
	$('form._fajax').live('submit', function(e) {
		e.preventDefault();
		var donotCleanInputs = [
			'#h1',
			'#header-title',
			'#url',
			'#nav-name',
			'#meta-description',
			'#meta-keywords',
			'#teaser-text'
		];
		var ajaxMessage = $('#ajax_msg');
		var form        = $(this);
		ajaxMessage.text('');
		$.ajax({
			url        : form.attr('action'),
			type       : 'post',
			dataType   : 'json',
			data       : form.serialize(),
			beforeSend : function() {
				ajaxMessage.slideDown().removeClass('error').addClass('success').text('Working...');;
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
					if(typeof callback != 'undefined' && callback != null) {
						eval(callback + '()');
					}
					ajaxMessage.html(response.responseText).fadeOut(_FADE_SLOW);
				}
				else {
					$(form).find('input:text').not(donotCleanInputs.join(',')).val('');
					ajaxMessage.removeClass('success').addClass('error').html(response.responseText);
				}
			},
			error: function(err) {
				ajaxMessage.removeClass('success').addClass('error').text('An error occured');
			}
		})
	})

	//seotoaster edit item link
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

	//seotoaster gallery links
	$('a._lbox').fancybox();

	$('#ajax_msg').click(function(){
		$(this).text('').slideUp();
	})

	//publishPages();
});

function loginCheck() {
	if($.cookie('PHPSESSID') === null) {
		showModalMessage('Session expired', 'Your session is expired! Please, login again', function() {
			top.location.href = $('#website_url').val();
		})
		return false;
	}
	return true;
}

function showModalMessage(title, msg, callback) {
	var messageScreen = $('<div class="info-message"></div>').html(msg);
	$(messageScreen).dialog({
		modal     : true,
		title     : title,
		resizable : false,
		buttons   : {
			Ok: function() {
				$(this).dialog('close');
				if(callback) {
					callback();
				}
			}
		}
	}).css({background : '#eee'});
	$('.ui-widget-content').css({background : '#eee'}).addClass('ui-corner-all');
}

function publishPages() {
	if(!top.$('#__tpopup').length) {
		$.get($('#website_url').val() + 'backend/backend_page/publishpages/');
	}
}
