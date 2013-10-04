$(function () {
	$('#pageCategory').hide();
	$('#templateId').prev().hide(); //hide label for the hidden field 'templateId'

	var _elements = [$('#header-title'), $('#url'), $('#nav-name')];
	var menuSelector = $('input.menu-selector');
	var isNewPage = !$('#pageId').val();
	var selectedOpt = $('#pageCategory').val();

	$('#optimized').val($('#toggle-optimized').attr('checked') ? 1 : 0);


	$(document).on('click', '.template_name',function () { // click on template name in templates list
		var templateId = $(this).parent().find('input[name="template-id"]').val();
		$('#templateId').val(templateId);
		$('#curr-template').text(templateId);
		$('#templatelist').removeClass('show');
	}).on('click', '.template_delete',function () { // click on the delete link of the template
			deleteTemplate($(this).closest('.template_item'));
			return false;
		}).on('click', 'input.menu-selector',function (e) { // main menu radio click
			checkMenu($(e.currentTarget).attr('id'), selectedOpt);
		}).on('click', '#toggle-optimized',function () { // optimized checkbox click
			var optCheck = $(this);
			var optimized = optCheck.attr('checked') ? 1 : 0;
			if (!optimized) {
				showConfirm('Are you sure? You will lose experts optimization once you save your changes !', function () {
					toggleOptimized(optimized);
				}, function () {
					optCheck.attr('checked', true);
				});
			} else {
				toggleOptimized(optimized);
			}
		}).on('click', '#published',function () {
			var draft = $('#draft');
			if (draft.length) {
				draft.val(($(this).prop('checked') ? 0 : 1));
			}
		}).on('blur', '#datepicker', function () {
			$('#publish-at').val($(this).val());
		});

	// if this is a page creation, register auto-populate and un-check all menu's radios

	if (isNewPage) {
		$('#h1').keyup(function () {
			var currentValue = $(this).val();
			$(_elements).each(function () {
				$(this).val(currentValue);
			});
		});
		menuSelector.each(function () {
			$(this).attr('checked', false);
		});
	} else {
		checkMenu();
	}
});

function toggleOptimized(optimized) {
	$('#optimized').val(optimized);
	$.post($('#website_url').val() + 'backend/backend_page/toggleoptimized/', {
		pid: $('#pageId').val(),
		optimized: optimized
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

function datepickerCallback() {
	$('#publish-at').val($(this).val());
}

function checkMenu(currentMenuItem, selectedOption) {
	var _MAIN_MENU_ID = 'inMenu-1';
	var _STATIC_MENU_ID = 'inMenu-2';
	var _NO_MENU_ID = 'inMenu-0';
	var pageId = $('#pageId').val();
	var selector = $('#pageCategory');
	var websiteUrl = $('#website_url').val();

	if ((typeof currentMenuItem == 'undefined' ) || !currentMenuItem) {
		currentMenuItem = $('.menu-selector:checked').attr('id');
	}

	switch (currentMenuItem) {
		case _STATIC_MENU_ID:
			$('.menu-info').text('This page is in flat menu');
		case _NO_MENU_ID:
			if (!selector.find('option[value="-1"]').length) {
				selector.prepend($('<option />').val('-1').text('No category'));
			}
			selector.val(-1);
			selector.hide();
			if (currentMenuItem == _NO_MENU_ID) {
				$('.menu-info').text('This page is not in menu');
			}
			break;
		case _MAIN_MENU_ID:
			selector.find('option[value="-1"]').remove();
			if (!pageId) {
				selector.val(-4);
			} else {
				if (typeof selectedOption != 'undefined') {
					selector.val(selectedOption);
				}
			}
			selector.show();
			break;
	}
}

function deleteTemplate(templateContainer) {
	smoke.confirm('Do you really want to remove this template?', function (e) {
		if (e) {
			$.ajax({
				url: $('#website_url').val() + 'backend/backend_theme/deletetemplate/',
				type: 'post',
				data: {"id": templateContainer.find('input[name="template-id"]').val()},
				success: function (response) {
					if (response.error == false) {
						templateContainer.remove();
						showTemplatesList();
					}
					//$('#ajax_msg').text(response.responseText).fadeIn().fadeOut(_FADE_FAST);
				},
				dataType: 'json'
			});
		} else {
		}
	}, {classname: 'error', ok: 'Yes', cancel: 'No', value: "existential dread"});
}


function showTemplatesList(){
	$.post($('#website_url').val() + 'backend/backend_theme/gettemplate/pageId/' + $('#pageId').val(), {listtemplates: 'all'}, function(response) {
		$('#templatelist').html(response).find('.content').accordion({
			heightStyle: 'content',
			header : '.template_header',
			collapsible: true
		});
		$('#templatelist .template_group').css({
			'max-height' : $('#templatelist .content').height() - $('#templatelist .template_header').outerHeight(true) * $('#templatelist .template_header').length
		});
	});
}