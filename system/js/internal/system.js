$(function(){
    var currentUrl = decodeURI(window.location.href);
    if(currentUrl && typeof currentUrl!='undefined'){
        var $currentLink = $("a[href='"+currentUrl+"']");
        $currentLink.addClass('current');
        if($currentLink.closest('.page').length){
            $currentLink.closest('.category').addClass('current');
        }
        if(currentUrl==$('#website_url').val()){
            $("a[href='"+$('#website_url').val()+"index.html']").addClass('current');
        }
    }
    /**
     * Seotoaster popup dialog
     */
    $(document).on('click', 'a.tpopup', function(e){
        if(!loginCheck()){
            return;
        }
        $('.__tpopup').dialog("close");
        e.preventDefault();
        var link = $(this);
        var pwidth = link.data('pwidth') || 960;
        var pheight = link.data('pheight') || 560;
        var popup = $(document.createElement('iframe')).attr({'scrolling' : 'no', 'frameborder' : 'no', 'allowTransparency' : 'allowTransparency', 'id' : 'toasterPopup'}).addClass('__tpopup');
        popup.parent().css({background : 'none'});
        popup.dialog({
            width     : pwidth,
            height    : pheight,
            resizable : false,
            draggable : true,
            modal     : true,
            open      : function(){
                this.onload = function(){
                    $(this).contents().find('.close, .save-and-close').on('click', function(){
                        var urlFrame = $('#toasterPopup').prop('src');
                        var restored = localStorage.getItem(generateStorageKey());
                        if(restored!==null && $.inArray('uploadthings',urlFrame.split('/')) == -1 ){
                            showConfirm('Hey, you did not save your work? Are you sure you want discard all changes?', function(){
                                localStorage.removeItem(generateStorageKey());
                                closePopup(popup);
                            });
                        }else{
                            closePopup(popup);
                        }
                    });
                }
                $(this).attr('src', link.data('url')).css({
                    width    : '100%',
                    height   : '100%',
                    padding  : '0px',
                    margin   : '0px',
                    overflow : 'hidden'
                });
                $('[aria-describedby="toasterPopup"] .ui-dialog-titlebar').remove();
            },
            close     : function(){
                $(this).remove();
            }
        }).parent().css({height : pheight+'px'});
    });
    //seotoaster delete item link
    $(document).on('click', 'a._tdelete', function(){
        var el = this;
        var url = $(this).attr('href');
        var callback = $(this).data('callback');
        var elId = $(this).data('eid');
        if((typeof url=='undefined') || !url || url=='javascript:;'){
            url = $(this).data('url');
        }
        smoke.confirm('You are about to remove an item. Are you sure?', function(e){
            if(e){
                $.ajax({
                    url: url+'id/'+ elId,
                    type: 'DELETE',
                    dataType: 'json',
                    beforeSend : showSpinner(el),
                    success: function(response){
                        var responseText = (response.hasOwnProperty(responseText)) ? response.responseText : 'Removed.';
                        showMessage(responseText, (!(typeof response.error=='undefined' || !response.error)));
                        if(typeof callback!='undefined'){
                            eval(callback+'()');
                        }
                        hideSpinner();
                    }
                });
            }else{
                $('.smoke-base').remove();
            }
        }, {classname : "error", 'ok' : 'Yes', 'cancel' : 'No'});
    });
    //seotoaster ajax form submiting
    $(document).on('submit', 'form._fajax', function(e){
        e.preventDefault();
        var donotCleanInputs = [
            '#h1', '#header-title', '#url', '#nav-name', '#meta-description', '#meta-keywords', '#teaser-text'
        ];
        var form = $(this);
        var callback = $(form).data('callback');
        var dialogBox = $(form).data('dialog-box');
        var dialogCallBack = $(form).data('dialog-box-callback');

        $.ajax({
            url        : form.attr('action'),
            type       : 'post',
            dataType   : 'json',
            data       : form.serialize(),
            beforeSend : showSpinner(),
            success    : function(response){
                if(!response.error){
                    if(form.hasClass('_reload')){
                        if(typeof response.responseText.redirectTo!='undefined'){
                            top.location.href = $('#website_url').val()+response.responseText.redirectTo;
                            return;
                        }
                        top.location.reload();
                        return;
                    }
                    //processing callback
                    if(typeof callback!='undefined' && callback!=null){
                        eval(callback+'()');
                    }
                    hideSpinner();
                    showMessage(response.responseText);
                }else{
                    hideSpinner();
                    if(typeof response.responseText === 'object'){
                        $.each(response.responseText, function(elementName, errorMessage){
                            var $field = form.find('[name="'+elementName+'"]');
                            $field.addClass('notvalid');
                            if($field.is(':checkbox') || $field.is(':radio') || $field.is(':hidden')){
                                if($field.parent().is('label')){
                                    $field.parent().prop('title', errorMessage).addClass('notvalid');
                                }else{
                                    var fieldId =  $field.prop('id');
                                    $(document).find('[for="'+ fieldId +'"]').prop('title', errorMessage).addClass('notvalid');
                                }
                            }else{
                                $field.prop('title', errorMessage);
                            }
                        });
                        $('.notvalid').on('change', function(){
                            var $field = $(this);
                            if($field.is(':checkbox') || $field.is(':radio') || $field.is(':hidden')){
                                if($field.parent().is('label')){
                                    $field.parent().tooltip('destroy').removeAttr('title', '').removeClass('notvalid');
                                }else{
                                    var fieldId =  $field.prop('id');
                                    $(document).find('[for="'+ fieldId +'"]').tooltip('destroy').removeAttr('title', '').removeClass('notvalid');
                                }
                            }else{
                                $field.tooltip('destroy').removeClass('notvalid').removeAttr('title');
                            }
                            $field.unbind();
                        });
                        showTooltip('.notvalid', 'error', 'right');
                    }else{
                        if(dialogBox && dialogCallBack && response.dialog){
                            smoke.confirm(response.responseText, function(e){
                                if (e){
                                    if (typeof dialogCallBack != 'undefined' && dialogCallBack != null) {
                                        eval(dialogCallBack + '()');
                                    }
                                }
                            }, {
                                ok: "Yes",
                                cancel: "No",
                                reverseButtons: true
                            });
                        }else{
                            smoke.alert(response.responseText, function () {
                                if (typeof callback != 'undefined' && callback != null) {
                                    eval(callback + '()');
                                }
                            }, {classname : "error"});
                        }
                    }
                }
            },
            error      : function(err){
                $('.smoke-base').remove();
                showMessage('Oops! sorry but something fishy is going on - try again or call for support.', true);
            }
        })
    });
    //seotoaster edit item link
    $(document).on('click', 'a._tedit', function(e){
        e.preventDefault();
        var handleUrl = $(this).data('url');
        if(!handleUrl || handleUrl=='undefined'){
            handleUrl = $(this).attr('href');
        }
        var eid = $(this).data('eid');
        $.post(handleUrl, {id : eid}, function(response){
            var formToLoad = $('#'+response.responseText.formId);
            for(var i in response.responseText.data){
                $('[name='+i+']').val(response.responseText.data[i]);
                if(i=='password'){
                    $('[name='+i+']').val('');
                }
            }
        })
    });
    //seotoaster gallery links
    if(jQuery.magnificPopup){
        $('._lbox').magnificPopup({
            type: 'image'  // other options
        });
        $('.img_gallery').each(function() { // the containers for all your galleries
            $(this).magnificPopup({
                delegate: 'a.gall',
                type: 'image',
                gallery: {
                    enabled:true
                }
            });
        });
    }
    //publishPages();
    checkboxRadioStyle();
    $(document).on('mouseup', '.seotoaster', function (e) {
        var container = $(".show-left, .show-right");
        if (container.has(e.target).length === 0){
            $('.show-left').hide("slide", { direction: "left"});
            $('.show-right').hide("slide", { direction: "right"});
            //return false;
        }
    }).on('click', '.closebutton .hide-block', function(){
        $('.show-left').hide("slide", { direction: "left"});
        $('.show-right').hide("slide", { direction: "right"});
        return false;
    });

    $(document).ajaxStop(function(){
        hideSpinner();
        checkboxRadioStyle();
    });
});
///////// Full screen //////////////
$(document).on('click', '.screen-size', function(e){
    var name = $(this).data('size');
    $('.closebutton').toggle();
    $(this).toggleClass('ticon-expand ticon-turn');
    $('body, #'+name+', .'+name).toggleClass('full-screen');
});
///////// Full screen //////////////
$(document).on('click', '#screen-expand', function(e){
    $(this).toggleClass('ticon-expand ticon-turn');
    var popup = $(window.parent.document).find('[aria-describedby="toasterPopup"]')
    popup.toggleClass('screen-expand');
    $('.content').toggleClass('screen-expand');
    var popupH = popup.height();
    if($('#edittemplate').length){
        $('.ace_editor').height(popupH-(560-390))
    }else{
        $('.ace_editor').height(popupH-(560-450))
    }
    editor.resize();
});
///////// Show tips when filling invalid fields //////////////
function showTooltip(el, addClass, position){
    $(el+"[title]").tooltip();
    var my = '';
    var at = '';
    switch(position){
        case 'right' :
            my = "left+10 center";
            at = "right center";
            break;

        case 'left' :
            my = "right-10 center";
            at = "left center";
            break;

        case 'top' :
            my = "center bottom-10";
            at = "center top";
            break;

        case 'bottom' :
            my = "center top+10";
            at = "center bottom";
            break;
    }

    $(el+"[title]").tooltip("option", {
        tooltipClass : addClass,
        position     : {
            my    : my,
            at    : at,
            using : function(position, feedback){
                $(this).css(position);
                $("<span>").addClass("arrow").addClass(feedback.vertical).addClass(feedback.horizontal).appendTo(this);
            }
        }
    });
}

///////// Show/Hide 'cropped' options //////////////
$(document).on('click', '[name="useImage"]:checkbox', function() {
    var form       = $(this).closest('form'),
        croppedImg = form.find('.cropped-img');

    croppedImg.fadeToggle(function() {
        if (!croppedImg.is(':visible')) {
            croppedImg.find('input:checkbox').prop('checked', false);
        }
    });
    form.find('.crop-size').hide().find('input:text').val('');
});
$(document).on('click', '.cropped-img input:checkbox', function() {
    var form     = $(this).closest('form'),
        cropSize = form.find('.crop-size');

//    form.find('.maxchars').fadeToggle();
    cropSize.fadeToggle(function() {
        if (!cropSize.is(':visible')) {
            cropSize.find('input:text').val('');
        }
    });
});
///////// Scrolling navigation Tabs //////////////
$(document).on('click', '.tabs-nav-wrap .arrow', function(){
    var $nav = $(this).nextAll('.ui-tabs-nav');
    var navScroll = $nav.scrollLeft();
    if($(this).hasClass('left')){
        $nav.stop().animate({
            scrollLeft : navScroll-200
        });
    }else if($(this).hasClass('right')){
        $nav.stop().animate({
            scrollLeft : navScroll+200
        });
    }
});
///////// checkbox & radio button //////////////
function checkboxRadioStyle(){
    if($('.seotoaster').length && !$('.ie8').length){
        $('input:checkbox, input:radio', '.seotoaster').not('.processed, .icon, .hidden').each(function(){
            var id = $(this).prop('id'), labelClass;
            if(!id.length){
                id = 'chr-'+Math.floor((Math.random()*100000)+1);
                $(this).prop('id', id);
            }
            if($(this).prop('class') || $(this).prop('class') !== 'undefined'){
                labelClass = $(this).prop('class');
            }
            $(this).addClass('processed');
            if($(this).is(':radio')){
                $(this).addClass('radio-upgrade filed-upgrade');
            }else{
                $(this).addClass('checkbox-upgrade filed-upgrade');
            }
            if(!$(this).closest('.btn-set').length){
                var $parent = $(this).parent('label');
                if($parent.length){
                    $parent.prop({
                        'for' : id
                    });
                    !$(this).hasClass('switcher') ? $(this).after('<span class="checkbox_radio '+labelClass+'"></span>') : $(this).after('<span class="checkbox_radio '+labelClass+'"><span></span></span>');
                }else{
                    !$(this).hasClass('switcher') ? $(this).wrap('<label for="'+id+'" class="checkbox_radio-wrap pointer '+labelClass+'"></label>').after('<span class="checkbox_radio"></span>') : $(this).wrap('<label for="'+id+'" class="checkbox_radio-wrap pointer '+labelClass+'"></label>').after('<span class="checkbox_radio"><span></span></span>');
                }
            }
        });
    }
}

function loginCheck(){
    if($.cookie('PHPSESSID')===null){
        showModalMessage('Session expired', 'Your session is expired! Please, login again', function(){
            top.location.href = $('#website_url').val();
        })
        return false;
    }
    return true;
}
function showMessage(msg, err, delay){
    if(err){
        smoke.alert(msg, function(e){
        }, {classname : "error"});
        return;
    }
    smoke.signal(msg);
    delay = (typeof(delay)=='undefined') ? 1300 : delay;
    $('.smoke-base').delay(delay).slideUp();
}
function showConfirm(msg, yesCallback, noCallback){
    smoke.confirm(msg, function(e){
        if(e){
            if(typeof yesCallback!='undefined'){
                yesCallback();
            }
        }else{
            if(typeof noCallback!='undefined'){
                noCallback();
            }
        }
    }, {classname : 'error', ok : 'Yes', cancel : 'No'});
}
function showSpinner(e){
    var el = (typeof text === 'string' ? e : 'body>.seotoaster');
    $(el).append('<span class="spinner"></span>');
}
function hideSpinner(){
    $('.spinner').remove();
}
function showLoader(text){
    var event = document.activeElement;
    $(event).addClass('btn-load').attr('disabled', 'true');
    var value = typeof text !== 'string' ? "Loading..." : text;
    $('body').append('<div class="seotoaster-loader">'+ value +'</div>');
}
function hideLoader(e){
    $('.btn-load').removeClass('btn-load').removeAttr('disabled', 'false');
    $('.seotoaster-loader').remove();
}
function publishPages(){
    if(!top.$('#__tpopup').length){
        $.get($('#website_url').val()+'backend/backend_page/publishpages/');
    }
}
function closePopup(frame){
    if(frame.contents().find('div.seotoaster').hasClass('refreshOnClose')){
        window.parent.location.reload();
    }
    if(typeof frame.dialog!='undefined'){
        frame.dialog('close');
    }else{
        console.log('Alarm! Something went wrong!');
    }
}
function generateStorageKey(){
    if($('#frm_content').length){
        var actionUrlComponents = $('#frm_content').prop('action').split('/');
         var storageKey = actionUrlComponents[5]+actionUrlComponents[7];
                console.dir(actionUrlComponents);
                if(typeof actionUrlComponents[9]=='undefined') {
                    storageKey += $('#page_id').val();
                } else {
                    if (actionUrlComponents[10]=='pageId' && typeof actionUrlComponents[11] != 'undefined') {
                        storageKey += actionUrlComponents[9] + actionUrlComponents[11];
                    } else {
                        storageKey += actionUrlComponents[9]
                    }
                }
        return storageKey;
    }
    return null;
}
function showMailMessageEdit(trigger, callback){
    $.getJSON($('#website_url').val()+'backend/backend_config/mailmessage/', {
        'trigger' : trigger
    }, function(response){
        $(msgEditScreen).remove();
        var msgEditScreen = $('<div class="msg-edit-screen"></div>').append($('<textarea id="trigger-msg" rows="10"></textarea>').val(response.responseText).css({
            resizable : "none"
        }));
        $('#trigger-msg').val(response.responseText);
        msgEditScreen.dialog({
            modal     : true,
            title     : 'Edit mail message before sending',
            width     : 600,
            resizable : false,
            show      : 'clip',
            hide      : 'clip',
            draggable : false,
            buttons   : [
                {
                    text  : "Okay",
                    click : function(e){
                        msgEditScreen.dialog('close');
                        callback($('#trigger-msg').val());
                    }
                }
            ]
        });
    }, 'json');
}