var doc = $(document);

$(function () {
    var currentUrl = decodeURI(window.location.href);
    if (currentUrl && typeof currentUrl != 'undefined') {
        $("a[href='" + currentUrl + "']").addClass('current');
        if (currentUrl == $('#website_url').val()) {
            $("a[href='" + $('#website_url').val() + "index.html']").addClass('current');
        }
    }

    /**
     * Seotoaster popup dialog
     */
    doc.on('click', 'a.tpopup', function (e) {
        if (!loginCheck()) {
            return;
        }
        e.preventDefault();
        var link = $(this);
        var pwidth = link.data('pwidth') || 960;
        var pheight = link.data('pheight') || 560;
        var popup = $(document.createElement('iframe')).attr({'scrolling': 'no', 'frameborder': 'no', 'allowTransparency': 'allowTransparency', 'id': 'toasterPopup'}).addClass('__tpopup');
        popup.parent().css({background: 'none'});

        popup.dialog({
            width: pwidth,
            height: pheight,
            resizable: false,
            draggable: true,
            modal: true,
            open: function () {
                this.onload = function () {
                    $(this).contents().find('.close, .save-and-close').on('click', function () {
                        var restored = localStorage.getItem(generateStorageKey());
                        if (restored !== null) {
                            showConfirm('Hey, you did not save your work? Are you sure you want discard all changes?', function () {
                                localStorage.removeItem(generateStorageKey());
                                closePopup(popup);
                            });
                        } else {
                            closePopup(popup);
                        }
                    });
                }
                $(this).attr('src', link.data('url')).css({
                    width: '100%',
                    height: '100%',
                    padding: '0px',
                    margin: '0px',
                    overflow: 'hidden'
                });
                $('.ui-dialog-titlebar').remove();
            },
            close: function () {
                $(this).remove();
            }
        }).parent().css({height: pheight + 'px'});
    });


    //seotoaster delete item link
    doc.on('click', 'a._tdelete', function () {
        var url = $(this).attr('href');
        var callback = $(this).data('callback');
        var elId = $(this).data('eid');
        if ((typeof url == 'undefined') || !url || url == 'javascript:;') {
            url = $(this).data('url');
        }
        smoke.confirm('You are about to remove an item. Are you sure?', function (e) {
            if (e) {
                $.post(url, {id: elId}, function (response) {
                    var responseText = (response.hasOwnProperty(responseText)) ? response.responseText : 'Removed.';
                    showMessage(responseText, (!(typeof response.error == 'undefined' || !response.error)));
                    if (typeof callback != 'undefined') {
                        eval(callback + '()');
                    }
                })
            } else {
                $('.smoke-base').remove();
            }
        }, {classname: "alert-error", 'ok': 'Yes', 'cancel': 'No'});
    });

    //seotoaster ajax form submiting
    doc.on('submit', 'form._fajax', function (e) {
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
        var form = $(this);
        var callback = $(form).data('callback');
        $.ajax({
            url: form.attr('action'),
            type: 'post',
            dataType: 'json',
            data: form.serialize(),
            beforeSend: function () {
                showSpinner(form);
            },
            success: function (response) {
                if (!response.error) {
                    if (form.hasClass('_reload')) {
                        if (typeof response.responseText.redirectTo != 'undefined') {
                            top.location.href = $('#website_url').val() + response.responseText.redirectTo;
                            return;
                        }
                        top.location.reload();
                        return;
                    }
                    //processing callback
                    if (typeof callback != 'undefined' && callback != null) {
                        eval(callback + '()');
                    }
                    hideSpinner(form);
                    showMessage(response.responseText);
                }
                else {
                    if (!$(form).data('norefresh')) {
                        $(form).find('input:text').not(donotCleanInputs.join(',')).val('');
                    }
                    hideSpinner(form);
                    smoke.alert(response.responseText, function () {
                        if (typeof callback != 'undefined' && callback != null) {
                            eval(callback + '()');
                        }
                    }, {classname: "alert-error"});
                }
            },
            error: function (err) {
                $('.smoke-base').remove();
                showMessage('Oops! sorry but something fishy is going on - try again or call for support.', true);
            }
        })
    })

    //seotoaster edit item link
    doc.on('click', 'a._tedit', function (e) {
        e.preventDefault();
        var handleUrl = $(this).data('url');
        if (!handleUrl || handleUrl == 'undefined') {
            handleUrl = $(this).attr('href');
        }
        var eid = $(this).data('eid');
        $.post(handleUrl, {id: eid}, function (response) {
            var formToLoad = $('#' + response.responseText.formId);
            for (var i in response.responseText.data) {
                $('[name=' + i + ']').val(response.responseText.data[i]);
                if (i == 'password') {
                    $('[name=' + i + ']').val('');
                }
            }
        })

    });

    //seotoaster gallery links
    if (jQuery.fancybox) {
        $('a._lbox').fancybox({
            'transitionIn': 'none',
            'transitionOut': 'none',
            'titlePosition': 'over'
        });
    }
    //publishPages();
    checkboxRadio();

    doc.on('click', '.closebutton .hide', function () {
        $('.show-left.show, .show-right.show').removeClass('show');
        return false;
    });

});

///////// Full screen //////////////
doc.on('click', '.screen-size', function (e) {
    var name = $(this).data('size');
    $('.closebutton').toggleClass('hidden');
    $(this).toggleClass('error');
    $('#' + name + ', .' + name).toggleClass('full-screen');
});

///////// Show/Hide 'cropped' options //////////////
doc.on('click', '[name="useImage"]', function () {
    $(this).closest('form').find('.cropped-img').fadeToggle();
});

///////// checkbox & radio button //////////////
function checkboxRadio() {
    $(':checkbox, :radio').not('.processed, .icon').each(function () {
        if (!$(this).closest('.btn-set').length) {
            if ($(this).parent('label').length) {
                $(this).after('<span></span>');
            } else {
                $(this).wrap('<label class="checkbox_radio"></label>').after('<span></span>');
            }
        }
        $(this).addClass('processed');
    });
}


function loginCheck() {
    if ($.cookie('PHPSESSID') === null) {
        showModalMessage('Session expired', 'Your session is expired! Please, login again', function () {
            top.location.href = $('#website_url').val();
        })
        return false;
    }
    return true;
}

function showMessage(msg, err, delay) {
    if (err) {
        smoke.alert(msg, function (e) {
        }, {classname: "alert-error"});
        return;
    }
    smoke.signal(msg);
    delay = (typeof(delay) == 'undefined') ? 1300 : delay;
    $('.smoke-base').delay(delay).slideUp();

}

function showConfirm(msg, yesCallback, noCallback) {
    smoke.confirm(msg, function (e) {
        if (e) {
            if (typeof yesCallback != 'undefined') {
                yesCallback();
            }
        } else {
            if (typeof noCallback != 'undefined') {
                noCallback();
            }
        }
    }, {classname: 'alert-error', ok: 'Yes', cancel: 'No'});
}

function showSpinner(e) {
    $(e).closest('[class*="content"]').append('<div class="spinner"></div>');
}

function hideSpinner(e) {
    $('.spinner').remove();
}

function publishPages() {
    if (!top.$('#__tpopup').length) {
        $.get($('#website_url').val() + 'backend/backend_page/publishpages/');
    }
}

function closePopup(frame) {
    if (frame.contents().find('div.seotoaster').hasClass('refreshOnClose')) {
        window.parent.location.reload();
    }

    if (typeof frame.dialog != 'undefined') {
        frame.dialog('close');
    } else {
        console.log('Alarm! Something went wrong!');
    }
}

function generateStorageKey() {
    if ($('#frm_content').length) {
        var actionUrlComponents = $('#frm_content').prop('action').split('/');
        return actionUrlComponents[5] + actionUrlComponents[7] + (typeof actionUrlComponents[9] == 'undefined' ? $('#page_id').val() : actionUrlComponents[9]);
    }
    return null;
}

function showMailMessageEdit(trigger, callback) {
    $.getJSON($('#website_url').val() + 'backend/backend_config/mailmessage/', {
        'trigger': trigger
    }, function (response) {
        $(msgEditScreen).remove();
        var msgEditScreen = $('<div class="msg-edit-screen"></div>').append($('<textarea id="trigger-msg"></textarea>').val(response.responseText).css({
            width: '555px',
            height: '155px',
            resizable: "none"
        }));
        $('#trigger-msg').val(response.responseText);
        msgEditScreen.dialog({
            modal: true,
            title: 'Edit mail message before sending',
            width: 600,
            height: 300,
            resizable: false,
            show: 'clip',
            hide: 'clip',
            draggable: false,
            buttons: [
                {
                    text: "Okay",
                    click: function (e) {
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