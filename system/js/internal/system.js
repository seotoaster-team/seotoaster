$(function() {
	_FADE_SLOW   = 5000;
	_FADE_NORMAL = 1500;
	_FADE_FAST   = 700;
	_FADE_FLASH  = 300;

	/**
	 * Seotoaster popup dialog
	 */
	$(document).on('click', 'a.tpopup', function(e) {
		if(!loginCheck()) {
			return;
		}
		e.preventDefault();
		var link    = $(this);
		var pwidth  = link.data('pwidth') || 960;
		var pheight = link.data('pheight') || 580;
		var popup = $(document.createElement('iframe')).attr({'scrolling' : 'no', 'frameborder' : 'no', 'allowTransparency' : 'allowTransparency', 'id' : 'topastePopup'}).addClass('__tpopup rounded3px');
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
	$(document).on('click', 'a._tdelete', function() {
		var url      = $(this).attr('href');
		var callback = $(this).data('callback');
		var elId     =  $(this).data('eid');
		if((typeof url == 'undefined') || !url || url == 'javascript:;') {
			url = $(this).data('url');
		}
		smoke.confirm('You are about to remove an item. Are you sure?', function(e) {
			if(e) {
				$.post(url, {id : elId}, function(response) {
					var responseText = (response.hasOwnProperty(responseText)) ? response.responseText : 'Removed.';
					showMessage(responseText, (!(typeof response.error == 'undefined' || !response.error)));
                    if(typeof callback != 'undefined') {
						eval(callback + '()');
					}
				})
			} else {
				$('.smoke-base').remove();
			}
		}, {classname:"errors", 'ok':'Yes', 'cancel':'No'});
	});

	//seotoaster close popup window button
	$(document).on('click', '.closebutton, .save-and-close', function() {
		var restored = localStorage.getItem(generateStorageKey());
		if(restored !== null) {
			showConfirm('Hey, you did not save your work? Are you sure you want discard all changes?', function() {
				localStorage.removeItem(generateStorageKey());
				closePopup();
			});
		} else {
			closePopup();
		}
	});

	//seotoaster ajax form submiting
	$(document).on('submit', 'form._fajax', function(e) {
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
				showSpinner();
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
					hideSpinner();
					showMessage(response.responseText);
				}
				else {
					if(!$(form).data('norefresh')) {
						$(form).find('input:text').not(donotCleanInputs.join(',')).val('');
					}
					hideSpinner();
					smoke.alert(response.responseText, {classname:"errors"});
				}
			},
			error: function(err) {
				$('.smoke-base').remove();
				showMessage('Oops! sorry but something fishy is going on - try again or call for support.', true);
			}
		})
	})

	//seotoaster edit item link
	$(document).on('click', 'a._tedit', function(e) {
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

	});
	//seotoaster gallery links
	if(jQuery.fancybox) {
		$('a._lbox').fancybox();
	}
	//publishPages();

    $(document).on('dblclick', '.container-wrapper', function(e){
        if (!e.ctrlKey) return false;

        var self = this,
            url = $(this).find('.tpopup.generator-links').data('url'),
            editContainer = $('<div></div>');
        $(editContainer).insertBefore($(this));
        $.getJSON(url, function(response){
            var editor = $('<textarea>').appendTo(editContainer);
            editor.val(response.content)
                  .height($(self).height())
                  .width($(self).width());
            var redactor = editor.redactor({
                lang: 'en',
                toolbar: false,
                autoformat: false
            });
            $(self).hide();

            var btnCancel = $('<input type="button" class="btn" value="Cancel" />');
                btnCancel.on('click', function(){ redactor.destroy(); $(editContainer).remove(); $(self).show(); });

            var btnSave = $('<input type="button" class="btn" value="Save" />');
                btnSave.on('click', function(){
                    var data = {
                        content: redactor.getCodeEditor(),
                        containerType : response.containerType,
                        containerName : response.name,
                        pageId        : response.pageId,
                        containerId   : response.id,
                        published     : (response.published ? 1 : 0),
                        publishOn     : response.publishingDate
                    };

                    $.post(url, data, function(resp){
                        if (resp.hasOwnProperty('error') && !!resp.error === false) {
                            window.location.reload();
                        }
                    }, 'json');
                });

            redactor.$frame.before(btnSave)
                    .before(btnCancel);
        });
    });
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

function showMessage(msg, err) {
	if(err) {
		smoke.alert(msg, {classname:"errors"});
		return;
	}
	smoke.signal(msg);
	$('.smoke-base').delay(1300).slideUp();
}

function showConfirm(msg, yesCallback, noCallback) {
	smoke.confirm(msg, function(e) {
		if(e) {
			if(typeof yesCallback != 'undefined') {
				yesCallback();
			}
		} else {
		    if(typeof noCallback != 'undefined') {
			    noCallback();
		    }
		}
	}, {classname : 'errors', ok : 'Yes', cancel : 'No'});
}

function showSpinner() {
	smoke.signal('<img src="' + $('#website_url').val() + 'system/images/loading.gif" alt="working..." />', 30000);
}

function hideSpinner() {
	$('.smoke-base').hide();
}

function publishPages() {
	if(!top.$('#__tpopup').length) {
		$.get($('#website_url').val() + 'backend/backend_page/publishpages/');
	}
}

function closePopup() {
	if(window.parent.jQuery('.__tpopup').contents().find('div.seotoaster').hasClass('refreshOnClose')) {
		window.parent.location.reload();
		window.parent.jQuery('.__tpopup').dialog('close');
	}
	//parent.$('iframe').dialog('close');
	if(typeof window.parent.$('iframe').dialog != 'undefined') {
		window.parent.$('iframe').dialog('close');
	} else {
		console.log('Alarm! Something went wrong!');
	}
}

function generateStorageKey() {
	if($('#frm_content').length) {
		var actionUrlComponents = $('#frm_content').attr('action').split('/');
		return actionUrlComponents[5] + actionUrlComponents[7] + (typeof actionUrlComponents[9] == 'undefined' ? $('#page_id').val() : actionUrlComponents[9]);
	}
	return null;
}

function showMailMessageEdit(trigger, callback) {
    $.getJSON($('#website_url').val() + 'backend/backend_config/mailmessage/', {
        'trigger' : trigger
    }, function(response) {
        $(msgEditScreen).remove();
        var msgEditScreen = $('<div class="msg-edit-screen"></div>').append($('<textarea id="trigger-msg"></textarea>').val(response.responseText).css({
            width  : '555px',
            height : '155px',
            resizable: "none"
        }));
        $('#trigger-msg').val(response.responseText);
        msgEditScreen.dialog({
            modal: true,
            title: 'Edit message title',
            width: 600,
            height: 300,
            resizable: false,
            show: 'clip',
            hide: 'clip',
            buttons: [
                {
                    text: "Okay",
                    click: function(e) {
                        msgEditScreen.dialog('close');
                        callback($('#trigger-msg').val());
                    }
                }
            ]
        }).parent().css({
            background: '#DAE8ED'
        });
    }, 'json');
}
