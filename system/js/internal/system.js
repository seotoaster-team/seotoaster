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
		link    = $(this);
		pwidth  = link.data('pwidth') || 960;
		pheight = link.data('pheight') || 650;
		if(top.$('#__tpopup').length) {

			var currUrl    = top.$('#__tpopup').attr('src');
			var currWidth  = top.$('#__tpopup').css('width');
			var currHeight = top.$('#__tpopup').css('height');

			top.$('#__tpopup').dialog('option', {
				width  : pwidth,
				height : pheight
			});
			top.$('#__tpopup').attr('src', link.data('url')).css({
				width    : pwidth + 'px',
				height   : pheight + 'px'
			});
			top.$('#__tpopup').data('backurl', currUrl);
			top.$('#__tpopup').data('backwidth', currWidth);
			top.$('#__tpopup').data('backheight', currHeight);

			return;
		}
		top.$('#__tpopup').data('backurl', null);
		top.$('#__tpopup').data('backwidth', null);
		top.$('#__tpopup').data('backheight', null);
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

		//ceck if this popup was opened from other popup, then we need to go back to the previous popup
		if(typeof top.$('#__tpopup').data('backurl') != 'undefined' && top.$('#__tpopup').data('backurl') != null) {
			top.$('#__tpopup').dialog('option', {
				width  : top.$('#__tpopup').data('backwidth').replace('px', ''),
				height : top.$('#__tpopup').data('backheight').replace('px', '')
			});
			top.$('#__tpopup').css({
				width  : top.$('#__tpopup').data('backwidth'),
				height : top.$('#__tpopup').data('backheight')
			});
			top.$('#__tpopup').attr('src', top.$('#__tpopup').data('backurl'));
			top.$('#__tpopup').data('backurl', null);
			return;
		}

		//reload page if we are closing template or css edit dialog
		//probably needs to be changed to something more universal
		if($('#frm_template').length || $('#editcssform').length || $('#fa-pages-list').length) {
			top.location.reload();
		}
		top.$('#__tpopup').dialog('close');
	})

	$('#ajax_msg').click(function(){
		$(this).text('').fadeOut();
	})

	$('form._fajax').live('submit', function(e) {
		e.preventDefault();

		donotCleanInputs = [
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
				ajaxMessage.fadeIn().removeClass('ui-state-error').addClass('success').text('Working...');;
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
					ajaxMessage.removeClass('success').addClass('ui-state-error').html(response.responseText);
				}
			},
			error: function(err) {
				ajaxMessage.removeClass('success').addClass('ui-state-error').text('An error occured');
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

	$('a._lbox').fancybox();

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
	});
}

function publishPages() {
	if(!top.$('#__tpopup').length) {
		$.get($('#website_url').val() + 'backend/backend_page/publishpages/');
	}
}