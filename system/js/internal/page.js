$(function() {
	_MAIN_MENU_ID   = 'inMenu-1';
	_STATIC_MENU_ID = 'inMenu-2';
	_NO_MENU_ID     = 'inMenu-0';

	_elements = [$('#header-title'), $('#url'), $('#nav-name')]

	$('#pageCategory').hide();

	$('#templatelist').delegate('div.template_preview', 'click', function() {
		var templateId = $(this).find('input[name="template-id"]').val();
		$('#templateId').val(templateId);
		$('#curr-template').text(templateId);
		$('#templatelist').slideUp();
	});

	if(!$('#pageId').val()) {

		$('#h1').keyup(function() {
			var currentValue = $(this).val();
			$(_elements).each(function() {
				//if($(this).val().length <= $('#h1').val().length) {
					$(this).val(currentValue);
				//}
			})
		})

		$('input[type=radio]').each(function() {
			$(this).attr('checked', false);
		});

		$('input[type=radio]').click(function() {
			checkMenu($(this).attr('id'));
		})

	}
	else {
		checkMenu();
	}

})

function checkMenu(currentMenuItem) {
	if(!currentMenuItem) {
		currentMenuItem = $('input[type=radio]:checked').attr('id');
	}
	switch(currentMenuItem) {
		case _STATIC_MENU_ID:
			$.getJSON($('#website_url').val() + 'backend/backend_page/rendermenu/mtype/2', function(response) {
				$('#pageCategory').replaceWith(response.select);
				$('#pageCategory').hide();
			})
		break;
		case _MAIN_MENU_ID:
			$.getJSON($('#website_url').val() + 'backend/backend_page/rendermenu/mtype/1', function(response) {
				$('#pageCategory').replaceWith(response.select).show();
			})
		break;
		case _NO_MENU_ID:
			$.getJSON($('#website_url').val() + 'backend/backend_page/rendermenu/mtype/0', function(response) {
				$('#pageCategory').replaceWith(response.select).show();
			})
		break;
	}
}