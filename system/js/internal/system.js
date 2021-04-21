$(function(){
    var currentUrl = decodeURI(window.location.href);
    if(currentUrl && typeof currentUrl!='undefined'){
        var $currentLink = $("a[href='"+currentUrl+"']");
        $currentLink.addClass('current');
        if($currentLink.closest("li").length > 0 && $currentLink.closest("li").hasClass('category')){
                $currentLink.closest("li").addClass('category-current');
        }
        if($currentLink.closest('.page').length){
            var catEl = $currentLink.closest('.category');
            catEl.addClass('current');
            if(catEl.closest("li").length > 0 && $currentLink.closest("li").hasClass('page')){
                $currentLink.closest("li").addClass('page-current');
                catEl.closest("li").addClass('category-current');
            }
        }
        if(currentUrl==$('#website_url').val()){
            var indexEl = $("a[href='"+$('#website_url').val()+"index.html']");
            indexEl.addClass('current');
            if(indexEl.closest("li").length > 0){
                indexEl.closest("li").addClass('category-current');
            }
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
        var adminPanelEl = $(this).closest('ul');

        var iframeId = 'toasterPopupDraggable';
        if(adminPanelEl.length) {
            iframeId = 'toasterPopup';
        }

        var popup = $(document.createElement('iframe')).attr({'scrolling' : 'no', 'frameborder' : 'no', 'allowTransparency' : 'allowTransparency', 'id' : iframeId}).addClass('__tpopup');
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
                        var urlFrame = $('#'+iframeId).prop('src');
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
                if(adminPanelEl.length) {
                    $('[aria-describedby="'+ iframeId +'"] .ui-dialog-titlebar').remove();
                }
            },
            close     : function(){
                $(this).remove();
            }
        }).parent().css({height : pheight+'px'});
    });
    //seotoaster delete item link
    $(document).on('click', 'a._tdelete', function(){
        var el = this,
            url = $(this).attr('href'),
            callback = $(this).data('callback'),
            elId = $(this).data('eid'),
            ignoreCustomMessage = $(this).data('ignore-custom-message'),
            customDeleteMessage = $('#custom-delete-message').val(),
            deleteDefaultMessage = 'You are about to remove an item. Are you sure?';


        if((typeof url=='undefined') || !url || url=='javascript:;'){
            url = $(this).data('url');
        }

        if (customDeleteMessage && !ignoreCustomMessage) {
            deleteDefaultMessage = customDeleteMessage;
        }

        smoke.confirm(deleteDefaultMessage, function(e){
            if(e){
                $.ajax({
                    url: url+'id/'+ elId,
                    type: 'DELETE',
                    dataType: 'json',
                    beforeSend : showSpinner(el),
                    success: function(response){
                        var responseText = (response.hasOwnProperty(responseText)) ? response.responseText : 'Removed.';
                        var delay;
                        if(typeof response.responseText.userDeleteError !== 'undefined') {
                            responseText = response.responseText.userDeleteError.quote;
                            delay = 2000;
                        }

                        showMessage(responseText, (!(typeof response.error=='undefined' || !response.error)), 3000);
                        if(typeof callback!='undefined'){
                            eval(callback+'('+ delay +')');
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
        var handleUrl = $(this).data('url'),
            callback = $(this).data('callback');
        if(!handleUrl || handleUrl=='undefined'){
            handleUrl = $(this).attr('href');
        }
        var eid = $(this).data('eid');
        $.post(handleUrl, {id : eid}, function(response){
            var formToLoad = $('#'+response.responseText.formId);
            for(var i in response.responseText.data){
                if ($('[name='+i+']').length && $('[name='+i+']').is(':checkbox')) {
                    if (response.responseText.data[i] == '1') {
                        $('[name='+i+']').prop('checked', true);
                    } else {
                        $('[name='+i+']').prop('checked', false);
                    }
                } else {
                    $('[name='+i+']').val(response.responseText.data[i]);
                }

                if(i=='password'){
                    $('[name='+i+']').attr('placeholder', '********').val('');
                }
                if (i=='attributes') {
                    $.each(response.responseText.data[i], function(attrName, attrValue) {
                        $('#user-attributes-section').append('<div class="grid_6"><input type="text" class="user-custom-attribute-name" name="attrName[]" value="' + attrName + '"></div>' +
                        '<div class="grid_6"><input type="text" name="attrValue[]" value="' + attrValue + '"></div>');
                    })
                }
            }
            if (typeof callback != 'undefined' && callback != null) {
                eval(callback + '()');
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
    /// Show more widget ///
    var elNode = $(this).find('.show-more-widget-close');
    if(elNode.length > 0) {
        elNode.addClass('text-close').hide();
        $('.show-more-widget-button-show').on('click', function (e) {
            e.preventDefault();
            var curentNode = $(this).closest('.show-more-content').find('.show-more-widget-close');
            curentNode.show();
            $(this).hide();
        });
        $('.show-more-widget-button-less').on('click', function (e) {
            e.preventDefault();
            var curentNode = $(this).closest('.show-more-content').find('.show-more-widget-close'),
            showButton = $(this).closest('.show-more-content').find('.show-more-widget-button-show');
            curentNode.hide();
            showButton.show();
        });
    }
});
///////// Full screen //////////////
$(document).on('click', '.screen-size', function(e){
    e.preventDefault();
    var name = $(this).data('size');
    $('.closebutton').toggle();

    if($(this).data('type') == 'form') {
        if(!$(this).hasClass('open')) {
            $(this).addClass('open');
        }

        var screenSizeEl = $('.screen-size');

        $.each(screenSizeEl, function(key, el){
            if(!$(el).hasClass('open')) {
                $(el).toggle();
            } else {
                $(el).removeClass('open').toggleClass('ticon-expand ticon-turn');
            }
        });
    } else {
        $(this).toggleClass('ticon-expand ticon-turn');
    }

    $('body, #'+name+', .'+name).toggleClass('full-screen');
});

///////// Show/Hide 'Reply email setup' block //////////////
$(document).on('change', '#reply-email', function (e) {
    var el = e.currentTarget;
    if(el.checked) {
        $('.reply-info').hide();
    } else {
        $('.reply-info').show();
    }
});
///////// Full screen //////////////
$(document).on('click', '#screen-expand', function(e){
    $(this).toggleClass('ticon-expand ticon-turn');
    var popup = $(window.parent.document).find('[aria-describedby="toasterPopup"]');
    if(!popup.length) {
        popup = $(window.parent.document).find('[aria-describedby="toasterPopupDraggable"]');
    }
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
        $('.triple-switch').each(function(){
            $('input:radio', this).not('.swt-processed').each(function(){
                var id = $(this).prop('id'), labelClass;
                if(!id.length){
                    id = 'chr-'+Math.floor((Math.random()*100000)+1);
                    $(this).prop('id', id);
                }
                if($(this).prop('class') || $(this).prop('class') !== 'undefined'){
                    labelClass = $(this).prop('class');
                }
                $(this).after('<label for="'+ id +'" class="'+labelClass+'">'+ $(this).data("title") +'</label>');
                $(this).addClass('swt-processed');
            });
            if(!$(this).find('span').length) {
                $(this).append('<span></span>');
            }
        });

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
        });
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
function showConfirmCustom(msg, yesValue, noValue, yesCallback, noCallback){
    var yes = 'Yes',
        no = 'No';

    if(typeof yesValue != 'undefined'){
        yes = yesValue;
    }
    if(typeof noValue != 'undefined'){
        no = noValue;
    }
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
    }, {classname : 'error', ok : yes, cancel : no});
}
function showSpinner(e, customSelector){
    var el = (typeof e !== 'undefined' && typeof e === 'string' ? e : 'body>.seotoaster');
    if (typeof customSelector !== 'undefined' && typeof customSelector === 'string') {
        $(el).append('<span class="'+customSelector+'"></span>');
    } else {
        $(el).append('<span class="spinner"></span>');
    }
}
function hideSpinner(customSelector){
    if (typeof customSelector !== 'undefined' && typeof customSelector === 'string') {
        $(customSelector).remove();
    } else {
        $('.spinner').remove();
    }
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
function showMailMessageEdit(trigger, callback, recipient){
    $.getJSON($('#website_url').val()+'backend/backend_config/mailmessage/', {
        'trigger' : trigger,
        'recipient' : recipient
    }, function(response){
        $(msgEditScreen).remove();
        var msg = response.responseText.message,
            dialogTitle = response.responseText.dialogTitle,
            dialogOkay = response.responseText.dialogOkay;

        dialogTitle = (dialogTitle.length > 0) ? dialogTitle : 'Edit mail message before sending';
        dialogOkay = (dialogOkay.length > 0) ? dialogOkay : 'Okay';
        msg = (msg) ? response.responseText.message : 'success';

        var msgEditScreen = $('<div class="msg-edit-screen"></div>').append($('<textarea id="trigger-msg" rows="10"></textarea>').val(msg).css({
            resizable : "none"
        }));
        $(msgEditScreen).append('<div class="mt10px">' +
            '<label> Additional emails <a href="javascript:;" class="ticon-info tooltip icon18" title="You can enter emails separated by comma. ex: John@mail.com,Doe@mail.com"></a> : </label>' +
            '<input type="text" name="additional-emails" id="additional-emails" value="" />' +
            '</div>');

        $('#trigger-msg').val(msg);
        msgEditScreen.dialog({
            modal     : true,
            title     : dialogTitle,
            width     : 600,
            resizable : false,
            show      : 'clip',
            hide      : 'clip',
            draggable : false,
            buttons   : [
                {
                    text  : dialogOkay,
                    click : function(e){
                        var additionalEmails = $('#additional-emails').val(),
                        closeDialog = true;

                        if(additionalEmails.length) {
                            additionalEmails = additionalEmails.split(',');

                           var regularExpression = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

                            $.each(additionalEmails, function(key, email){
                                var clearEmail = email.toString().replace(/\s/g, ''),
                                isValidEmail = regularExpression.test(clearEmail);

                                if(!isValidEmail) {
                                    closeDialog = false;
                                    showMessage('Not valid email address - "' + clearEmail + '"', true, 3000);
                                }
                            });
                        }

                        if(closeDialog) {
                            msgEditScreen.dialog('close');
                            callback($('#trigger-msg').val(), $('#additional-emails').val());
                        }
                    }
                }
            ],
            close: function(event, ui){
                $(this).dialog('close').remove();
            }
        });
    }, 'json');
}
