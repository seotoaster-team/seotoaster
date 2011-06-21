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

	$('input[type=radio]').click(function() {
		checkMenu($(this).attr('id'));
	})

	if(!$('#pageId').val()) {
		$('#h1').keyup(function() {
			var currentValue = $(this).val();
			$(_elements).each(function() {
				$(this).val(currentValue);
			})
		})
		$('input[type=radio]').each(function() {
			$(this).attr('checked', false);
		});
	}
	else {
		checkMenu();
	}

})

function checkMenu(currentMenuItem) {
	var pageId = $('#pageId').val();
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
			$.getJSON($('#website_url').val() + 'backend/backend_page/rendermenu/mtype/1/pId/' + pageId, function(response) {
				$('#pageCategory').replaceWith(response.select).show();
			})
		break;
		case _NO_MENU_ID:
			$.getJSON($('#website_url').val() + 'backend/backend_page/rendermenu/mtype/0/pId/' + pageId, function(response) {
				$('#pageCategory').replaceWith(response.select).show();
			})
		break;
	}
}