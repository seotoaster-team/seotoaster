function toggleOptimized(optimized) {
    $('#optimized').val(optimized);
    $.post($('#website_url').val() + 'backend/backend_page/toggleoptimized/', {
        pid: $('#pageId').val(),
        optimized: optimized,
        secureToken: $('#frm-page').find('.secureToken').val()
    }, function (response) {
        $.each(response.data, function (key, val) {
            var field = $('[name=' + key + ']', $('#frm-page'));
            field.val(val);
            if (optimized) {
                field.attr('disabled', true).attr('readonly', 'readonly').addClass('noedit');
            } else {
                field.removeAttr('disabled').removeAttr('readonly').removeClass('noedit');
            }
        });
    }, 'json');
}

function toggleExternalLink(){
    var externalLinkStatusEl = $('#external-link-status'),
        disableFieldIds = ['h1', 'header-title', 'meta-keywords', 'meta-description', 'page-options', 'curr-template'],
        externalLinkStatus = ($('#external-link-switch').prop('checked') ? 1 : 0);
    if (externalLinkStatusEl.length) {
        externalLinkStatusEl.val(externalLinkStatus);
        if (!$('#toggle-optimized').is(':checked')) {
            $.each(disableFieldIds, function(key, fieldId){
                $('#'+fieldId).prop('readonly', externalLinkStatus).toggleClass('noedit');
            });
            if (externalLinkStatus) {
                $('.template-section').css({'visibility':'hidden'});
                $('#url').val($('#external-link').val());
                if($.trim($('#url').val()) === ''){
                    $('#url').prop('placeholder','Your link to an external website');
                }
            } else {
                $('#url').val($('#original-page-url').val());
                $('.template-section').css({'visibility':'visible'});
                $('#url').prop('placeholder','');
            }
        }
    }
}

function datepickerCallback() {
    $('#publish-at').val($(this).val());
}

function checkMenu(currentMenuItem, selectedOption) {
    var MAIN_MENU_ID = 'inMenu-1',
        STATIC_MENU_ID = 'inMenu-2',
        NO_MENU_ID = 'inMenu-0',
        pageId = $('#pageId').val(),
        selector = $('#pageCategory');

    if (currentMenuItem === undefined || !currentMenuItem) {
        currentMenuItem = $('.menu-selector:checked').attr('id');
    }

    switch (currentMenuItem) {
    case STATIC_MENU_ID:
        $('.menu-info').show().text('This page is in flat menu');
    case NO_MENU_ID:
        if (!selector.find('option[value="-1"]').length) {
            selector.prepend($('<option />').val('-1').text('No category'));
        }
        selector.val(-1);
        selector.hide();
        if (currentMenuItem === NO_MENU_ID) {
            $('.menu-info').show().text('This page is not in menu');
        }
        break;
    case MAIN_MENU_ID:
        selector.find('option[value="-1"]').remove();
        if (!pageId) {
            selector.val(-4);
        } else {
            if (selectedOption !== undefined) {
                selector.val(selectedOption);
            }
        }
        selector.show().next('.menu-info').hide();
        break;
    }
}

$(document).on('change', '#template-categories', function(){
    var cat = $(this).val();
    $('.template_group').hide();
    $('#'+cat).show();
});
function showTemplatesList() {
    var $templateList = $('#templatelist');
    $.post($('#website_url').val() + 'backend/backend_theme/gettemplate/pageId/' + $('#pageId').val(), {
        listtemplates: 'all',
        beforeSend: showSpinner('#templatelist')
    }, function (response) {
        $templateList.html(response);
        hideSpinner();
    });
}

$(function () {
    $('#pageCategory').hide();

    var elements = [$('#header-title'), $('#url'), $('#nav-name')],
        isNewPage = !$('#pageId').val(),
        selectedOpt = $('#pageCategory').val();

    $('#optimized').val($('#toggle-optimized').attr('checked') ? 1 : 0);

    if ($('#external-link-status').val() == 1 && !$('#toggle-optimized').attr('checked')) {
        toggleExternalLink();
    }

    $(document).on('click', '.template_name', function () { // click on template name in templates list
        var templateId = $(this).parent().find('input[name="template-id"]').val();
        $('#templateId').val(templateId).change();
        $('#curr-template').text(templateId);
        $('#templatelist').hide("slide", { direction: "right" });
    }).on('click', 'input.menu-selector', function (e) { // main menu radio click
        checkMenu($(e.currentTarget).attr('id'), selectedOpt);
    }).on('click', '#toggle-optimized', function () { // optimized checkbox click
        var optCheck = $(this),
            optimized = optCheck.is(':checked') ? 1 : 0;
        if (!optimized) {
            showConfirm('Are you sure? You will lose experts optimization once you save your changes !', function () {
                toggleOptimized(optimized);
            }, function () {
                optCheck.prop('checked', true);
            });
        } else {
            toggleOptimized(optimized);
        }
    }).on('click', '#published', function () {
        var draft = $('#draft');
        if (draft.length) {
            draft.val(($(this).prop('checked') ? 0 : 1));
        }
    }).on('blur', '#datepicker', function () {
        $('#publish-at').val($(this).val());
    }).on('change', '#external-link-switch', function(){
        toggleExternalLink()
    });

    // if this is a page creation, register auto-populate
    if (isNewPage) {
        $('#h1').keyup(function () {
            var currentValue = $(this).val();
            $(elements).each(function () {
                $(this).val(currentValue);
                $(this).removeClass('notvalid').removeAttr('title');
            });
        });
    }
    checkMenu();
});