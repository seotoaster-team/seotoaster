$(function() {
    if (!$.browser.msie) {
        var textarea = $('#template-content').hide().detach();
        textarea.insertBefore('#editor');
        window.editor = ace.edit("edittemplate");
        editor.setTheme("ace/theme/crimson_editor");
        var HTMLMode = require("ace/mode/html").Mode;
        editor.getSession().setMode(new HTMLMode());
        editor.getSession().setValue(textarea.val());
        editor.getSession().setUseWrapMode(true);
        editor.setShowPrintMargin(false);
    }

    $('#title').focus();
    $('#frm_template').on('submit', saveTemplate);

    $('#templatelist').on('click', 'div.template_preview', function(){
        var tplOldName   = $('#frm_template').find('#title').val();
        var lnkListPages = $('.listpages');
        $.post(
            $('#website_url').val()+'backend/backend_theme/gettemplate/',
            {'listtemplates': $(this).find('input[name="template-id"]').val()},
            function(response){
                if (response.error != false){
                    $('#frm_template').find('#title').val(response.responseText.name);
                    $.browser.msie ? $('#frm_template').find('#template-content').val(response.responseText.content) : editor.getSession().setValue(response.responseText.content);
                    $('#frm_template').find('#template_id').val(response.responseText.name);
                    $('#frm_template').find('#template-type').val(response.responseText.type);
                    $('#template_preview').attr('src', $('#website_url').val()+response.responseText.preview);

                    var dataUrl = lnkListPages.data('url');
                    lnkListPages.data('url', dataUrl.replace(tplOldName, response.responseText.name));
                    $.getJSON($('#website_url').val()+'backend/backend_theme/pagesviatemplate/', {
                        template: response.responseText.name
                    }, function(response) {
                        $('#pcount').text(response.pagesUsingTemplate);
                    })
                }
            },
            'json'
        );
        $('#templatelist').slideUp();
    }).on('click', 'div.template_delete', function(){
        deleteTemplate($(this).closest('div.template_item'));
        return false;
    });

    $('#listtemplates-btn').on('click', function(e){
        e.preventDefault();
        $.post(
            $('#website_url').val()+'backend/backend_theme/gettemplate/',
            {'listtemplates':'all', 'pageId' : $('#pageId').val()},
            function(html){
                $('#templatelist').html(html).slideDown().css('overflow-y', 'auto');
            },
            'html');
    });

    $('textarea').on('keydown', function(e) {
        if(e.ctrlKey && e.keyCode == 83) {
            e.preventDefault();
            saveTemplate();
        }
    })
});

function saveTemplate() {
    if (!$.browser.msie){
        var templateContent = editor.getSession().getValue();
    }
    $.ajax({
        url        : $(this).attr('action'),
        type       : 'post',
        dataType   : 'json',
        data: $.browser.msie ? $(this).serialize() : {
            content : templateContent,
            pageId : $('#pageId').val(),
            templateType : $('#template-type').val(),
            name : $('#title').val(),
            id: $('#template_id').val()
        },

        beforeSend : function() {showSpinner();},
        success : function(response) {
            hideSpinner();
            if (!response.error) {
                showMessage('Template saved');
                if (response.responseText == 'new') {
                    $('#title').val('');
                    editor.getSession().setValue('');
                }
            } else {
                if (typeof response.responseText === 'string'){
                    showMessage(response.responseText, true);
                } else {
                    showMessage(response.responseText.join('. '), true);
                }
            }
        },
        error: function(xhr, errorStatus) {
            showMessage(errorStatus, true);
        }
    });
    return false;
}

function deleteTemplate(templateContainer) {
    showConfirm('You are about to remove template. Are you sure?', function() {
        $.ajax({
            url: $('#website_url').val()+'backend/backend_theme/deletetemplate/',
            type: 'post',
            beforeSend: function() {showSpinner();},
            data: {"id": templateContainer.find('input[name="template-id"]').val()},
            success: function(response) {
                hideSpinner();
                if (!response.error){
                    templateContainer.remove();
                }
                showMessage(response.responseText, response.error);
            },
            dataType: 'json'
        });
    });
}


