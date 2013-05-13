$(function() {
	$('#pageCategory').hide();
	$('#templateId').prev().hide(); //hide label for the hidden field 'templateId'

    var _elements    = [$('#header-title'), $('#url'), $('#nav-name')];
    var menuSelector = $('input.menu-selector');
    var isNewPage    = !$('#pageId').val();

    $('#optimized').val($('#toggle-optimized').attr('checked') ? 1 : 0);


    $(document).on('click', 'div.template_preview', function() { // click on template preview in templates list
		var templateId = $(this).find('input[name="template-id"]').val();
		$('#templateId').val(templateId);
		$('#curr-template').text(templateId);
		$('#templatelist').slideUp();
	}).on('click', 'div.template_delete', function(){ // click on the delete link of the template
		deleteTemplate($(this).closest('div.template_item'));
		return false;
	}).on('click', 'input.menu-selector', function(e) { // main menu radio click
        checkMenu($(e.currentTarget).attr('id'));
    }).on('click', '#toggle-optimized', function() { // optimized checkbox click
        var optCheck  = $(this);
        var optimized = optCheck.attr('checked') ? 1 : 0;
        if(!optimized) {
            showConfirm('Are you sure? You will lose experts optimization once you save your changes !', function() {
                toggleOptimized(optimized);
            }, function() {
                optCheck.attr('checked', true);
            });
        } else {
            toggleOptimized(optimized);
        }
    }).on('click', '#published', function() {
        var draft = $('#draft');
        if(draft.length) {
            draft.val(($(this).prop('checked') ? 0 : 1));
        }
    }).on('blur', '#datepicker', function(){
        $('#publish-at').val($(this).val());
    });

    // if this is a page creation, register auto-populate and un-check all menu's radios

	if(isNewPage) {
		$('#h1').keyup(function() {
			var currentValue = $(this).val();
			$(_elements).each(function() { $(this).val(currentValue); });
		});
        menuSelector.each(function() {$(this).attr('checked', false);});
	} else {
		checkMenu();
	}
});

function toggleOptimized(optimized) {
    $('#optimized').val(optimized);
    $.post($('#website_url').val() + 'backend/backend_page/toggleoptimized/', {
        pid: $('#pageId').val(),
        optimized: optimized
    }, function(response) {
        $.each(response.data, function(key, val) {
            var field  = $('[name=' + key + ']', $('#frm-page'));
            field.val(val);
            if(optimized) {
                field.attr('disabled', true).attr('readonly', 'readonly').addClass('noedit');
            } else {
                field.removeAttr('disabled').removeAttr('readonly').removeClass('noedit');
            }
        });
    }, 'json');
}

function datepickerCallback() {
	$('#publish-at').val($(this).val());
}

function checkMenu(currentMenuItem) {
    var _MAIN_MENU_ID   = 'inMenu-1';
    var _STATIC_MENU_ID = 'inMenu-2';
    var _NO_MENU_ID     = 'inMenu-0';
	var pageId          = $('#pageId').val();
    var selector        = $('#pageCategory');
    var websiteUrl      = $('#website_url').val();

	if((typeof currentMenuItem == 'undefined' ) || !currentMenuItem) {
		currentMenuItem = $('.menu-selector:checked').attr('id');
	}

	switch(currentMenuItem) {
		case _STATIC_MENU_ID:
        case _NO_MENU_ID:
            var option = selector.find('option[value^="-"]');
            if(option.length) {
                option.val(-1).attr({selected:'selected'});
            } else {
                selector.prepend($('<option />').val('-1').text('Make your selection').attr({selected: 'selected'}));
            }
            selector.hide();
		break;
		case _MAIN_MENU_ID:
            if(!$('#pageId').val()) {
                selector.val(-4).attr({selected:'selected'});
            }
            selector.show();
		break;
	}
}

function deleteTemplate(templateContainer) {
    var messageScreen = $('<div class="info-message"></div>').css({color:'lavender'}).html('Do you really want to remove this template?');
	$(messageScreen).dialog({
		modal    : true,
		title    : 'Remove template?',
		resizable: false,
		buttons: {
			Yes: function() {
				$.ajax({
					url: $('#website_url').val()+'backend/backend_theme/deletetemplate/',
					type: 'post',
					data: {"id": templateContainer.find('input[name="template-id"]').val()},
					success: function(response) {
						if (response.error == false){
							templateContainer.remove();
						}
						$('#ajax_msg').text(response.responseText).fadeIn().fadeOut(_FADE_FAST);
					},
					dataType: 'json'
				});
				$(this).dialog('close');
			},
			No : function() {
				$(this).dialog('close');
			}
		}
	}).parent().css({background: 'indianred'});
}