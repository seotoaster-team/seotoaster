$(function() {
    //if (!$.browser.msie) {
        var textarea = $('#template-content').hide().detach();
        textarea.insertBefore('#editor');
        window.editor = ace.edit("edittemplate");
        editor.setTheme("ace/theme/crimson_editor");
        var HTMLMode = require("ace/mode/html").Mode;
        editor.getSession().setMode(new HTMLMode());
        editor.getSession().setValue(textarea.val());
        editor.getSession().setUseWrapMode(true);
        editor.setShowPrintMargin(false);
    //}

    $('#title').focus();
    $('#frm_template').on('submit', saveTemplate);
    showListPages();
    showTemplateList();

    $('#templatelist').on('click', '.template_name', function(){
        showSpinner();
        $('.template_item').removeClass('curr-template').find('.template-check').remove();
        $(this).before('<span class="template-check ticon-check icon16"/></span>').parent().addClass('curr-template');
        $.post(
            $('#website_url').val()+'backend/backend_theme/gettemplate/',
            {'listtemplates': $(this).parent().find('input[name="template-id"]').val()},
            function(response){
                if (response.error != false){
                    $('#frm_template').find('#title').val(response.responseText.name);
                    editor.getSession().setValue(response.responseText.content);
                    $('#frm_template').find('#template_id').val(response.responseText.name);
                    $('#frm_template').find('#template-type').val(response.responseText.type);

                    $.getJSON($('#website_url').val()+'backend/backend_theme/pagesviatemplate/', {
                        template: response.responseText.name
                    }, function(response) {
                        $('#pcount').text(response.pagesUsingTemplate);
                    });
                    showTemplateList();
                    showListPages();
                    hideSpinner();
                }
            },
            'json'
        );
        $('#templatelist').hide("slide", { direction: "right"});
    }).on('click', '.template_delete', function(){
        deleteTemplate($(this).closest('.template_item'));
        return false;
    });

    $(document).on('click', '#listpages-btn', function(e){
        e.preventDefault();
        $('#listpages').show("slide", { direction: "right"});
    });

    $(document).on('click', '#listtemplates-btn', function(e){
        e.preventDefault();
        var $templateList =  $('#templatelist');
        $templateList.show("slide", { direction : "right"});
    }).on('keydown', 'textarea', function(e) {
        if(e.ctrlKey && e.keyCode == 83) {
            e.preventDefault();
            saveTemplate();
        }
    }).on('change', '#template-categories', function(){
        var cat = $(this).val();
        $('.template_group').hide();
        $('#'+cat).show();
    });
});

function showTemplateList(e){
    var $templateList =  $('#templatelist');
	if(!$templateList.find('.content').length || e == 'update'){
		$.post(
			$('#website_url').val()+'backend/backend_theme/gettemplate/',
			{
                listtemplates : 'all',
                pageId        : $('#pageId').val(),
                beforeSend    : showSpinner('#templatelist')
            },
			function(html){
                $templateList.html(html);
                var templateName = $('#template_id').val();
                $('.template_item').removeClass('curr-template').find('.template-check').remove();
                $('.template_item').has('input[value="'+ templateName +'"]').addClass('curr-template').find('.template_name').before('<span class="template-check ticon-check icon16"/></span>');
                hideSpinner();
			},
			'html'
		);
	}
}

function showListPages(e){
	$.post(
		$('#website_url').val()+'backend/backend_page/listpages/',
		{'template': $('#template_id').val(), 'format': 'html'},
		function(html){
			$('#listpages').html(html);
		},
		'html'
	);
}

function saveTemplate() {
    //if (!$.browser.msie){
        var templateContent = editor.getSession().getValue();
    //}
    $.ajax({
        url        : $(this).attr('action'),
        type       : 'post',
        dataType   : 'json',
        data: {
            content : templateContent,
            pageId : $('#pageId').val(),
            templateType : $('#template-type').val(),
            name : $('#title').val(),
            id: $('#template_id').val(),
            secureToken: $('.template-secure-token').val()
        },
        beforeSend : function() {showSpinner();},
        success : function(response) {
            hideSpinner();
            if (!response.error) {
                showMessage('Template saved');
                if (response.responseText == 'new') {
                    $('#title').val('');
                    editor.getSession().setValue('');
                }else if (response.responseText == 'update') {
					showTemplateList(response.responseText);
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
            data: {"id": templateContainer.find('input[name="template-id"]').val(), secureToken: $('#frm_template').find('input[name="secureToken"]').val()},
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


