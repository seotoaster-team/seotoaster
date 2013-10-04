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
		showSpinner('#frm_template');
		$('.template_item').removeClass('curr-template').find('.template-check').remove();
		var templateName = $(this).parent().find('input[name="template-id"]').val();
		$(this).before('<span class="template-check icon-check"/></span>').parent().addClass('curr-template');
        //var tplOldName   = $('#frm_template').find('#title').val();
        var lnkListPages = $('#listpages-btn');
        $.post(
            $('#website_url').val()+'backend/backend_theme/gettemplate/',
            {'listtemplates': $(this).parent().find('input[name="template-id"]').val()},
            function(response){
                if (response.error != false){
                    $('#frm_template').find('#title').val(response.responseText.name);
                    editor.getSession().setValue(response.responseText.content);
                    $('#frm_template').find('#template_id').val(response.responseText.name);
                    $('#frm_template').find('#template-type').val(response.responseText.type);
                    //$('#template_preview').attr('src', $('#website_url').val()+response.responseText.preview);

                    var dataUrl = lnkListPages.data('url');
                    //lnkListPages.data('url', dataUrl.replace(tplOldName, response.responseText.name));
                    $.getJSON($('#website_url').val()+'backend/backend_theme/pagesviatemplate/', {
                        template: response.responseText.name
                    }, function(response) {
                        consolelog(response.responseText.name)
                        $('#pcount').text(response.pagesUsingTemplate);
                    })
					showTemplateList();
					showListPages();
					hideSpinner();
                }
            },
            'json'
        );
        $('#templatelist').removeClass('show');
    }).on('click', '.template_delete', function(){
        deleteTemplate($(this).closest('.template_item'));
        return false;
    });

	$(document).on('click', '#listpages-btn', function(e){
		e.preventDefault();
		$('#listpages').addClass('show');
	}).on('click', '.closebutton .hide, .show~#frm_template', function(e) {
		$('#templatelist, #listpages').removeClass('show');
		return false;
	});

    $(document).on('click', '#listtemplates-btn', function(e){
        e.preventDefault();
		$('#templatelist').addClass('show');
    }).on('click', '.closebutton .hide, .show~#frm_template', function(e) {
		$('#templatelist, #listpages').removeClass('show');
		return false;
	});

    $('textarea').on('keydown', function(e) {
        if(e.ctrlKey && e.keyCode == 83) {
            e.preventDefault();
            saveTemplate();
        }
    })
});

function showTemplateList(e){
	if(!$('#templatelist').find('.content').length || e == 'update'){
		$.post(
			$('#website_url').val()+'backend/backend_theme/gettemplate/',
			{'listtemplates':'all', 'pageId' : $('#pageId').val()},
			function(html){
				$('#templatelist').html(html).find('.content').accordion({
					heightStyle: 'content',
					header : '.template_header',
					collapsible: true
				});
				$('#templatelist .template_group').css({
					'max-height' : $('#templatelist .content').height() - $('#templatelist .template_header').outerHeight(true) * $('#templatelist .template_header').length
				});
				var templateName = $('#template_id').val();
				$('.template_item').removeClass('curr-template').find('.template-check').remove();
				$('.template_item').has('input[value="'+ templateName +'"]').addClass('curr-template').find('.template_name').before('<span class="template-check icon-check"/></span>');
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
			$('#listpages').html(html).find('.content').accordion({
				heightStyle: 'content',
				header : '.template_header'
			});
			$('#listpages .template_group').css({
				'max-height' : $('#listpages .content').height() - $('#listpages .template_header').outerHeight(true) * $('#listpages .template_header').length
			});
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
            id: $('#template_id').val()
        },

        beforeSend : function() {showSpinner("#frm_template");},
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
            beforeSend: function() {showSpinner(templateContainer);},
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


