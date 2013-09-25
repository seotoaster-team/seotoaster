$(function () {
	$('#mass-del').click(function () {
		var pagesIds = [];
		$('.page-remove:checked').each(function () {
			pagesIds.push($(this).parent().attr('id'));
		});
		if (!pagesIds.length) {
			showMessage('Select at least one item please', true);
			return;
		}
		showConfirm('You are about to remove one or many pages. Are you sure?', function () {
			$.ajax({
				url: $('#website_url').val() + 'backend/backend_page/delete/',
				type: 'post',
				data: {
					id: pagesIds
				},
				dataType: 'json',
				beforeSend: function () {
					showSpinner();
				},
				success: function (response) {
					hideSpinner();
					showMessage(response.responseText);
					$(pagesIds).each(function () {
						$('#' + this).remove();
					})
				}
			});
		});
	});

	$('#sortable-main>div:first-child, #sortable-main .drop-list').sortable({
		start: function(event, ui) {
			$('#sortable-main>div').css('overflow','visible');
			$('.drop-zone .drop-text').hide();
			$('.drop-zone .drop-list').show();
		},
		handle: '.catmove',
		cancel: '.nosort, .catmove>*',
		helper: 'clone',
		stop: saveCategoriesOrder
	});

	$('.organise').sortable({
		connectWith: '.organise',
		start: function(event, ui) {
			$('#sortable-main>div').css('overflow','visible');
			$('.drop-zone .drop-text').hide();
			$('.drop-zone .drop-list').show();
		},
		receive: function () {
			var pages = [];
			$(this).find('li').each(function () {
				pages.push($(this).attr('id'));
			});
			renewedData = {
				act: 'renew',
				menu: $(this).parent().data('menu'),
				categoryId: $(this).parent().attr('id'),
				pages: pages
			}
			$.post($('#website_url').val() + 'backend/backend_page/organize/', renewedData)
		},
		stop: saveCategoriesOrder
	});


	$('.collapse-all').click(function () {
		$('.organise').not($(this).parent().next()).slideUp();
		$('.collapse-all').not(this).removeClass('icon-arrow-down').addClass('icon-arrow-right');
		$(this).parent().next().slideToggle();
		$(this).toggleClass('icon-arrow-down icon-arrow-right');
	})

	$('#collapse-global').click(function () {
		$('#sortable-main>div:first-child .organise').slideUp();
		$('.collapse-all').removeClass('icon-arrow-down').addClass('icon-arrow-right');
	})

	$('#expand-global').click(function () {
		$('#sortable-main>div:first-child .organise').slideDown();
		$('.collapse-all').removeClass('icon-arrow-right').addClass('icon-arrow-down');
	})

	var state = false;
	$('.check-all').on('click', function () {
		$(this).toggleClass('checked');
		state = !state;
		if (state) {
			$(this).parent().next().find('input:checkbox').click();
		} else {
			$(this).parent().next().find('input:checkbox').click();
		}
	});
});


function saveCategoriesOrder() {
	var rankins = 0;
	var ordered = [];
	$('#sortable-main').find('.category-data').each(function () {
		$(this).find('.ranking').html('#' + ++rankins);
		ordered.push($(this).attr('id'));
		$(this).find('.organise li').each(function () {
			ordered.push($(this).attr('id'));
		})
	});
	$('#sortable-main>div').css('overflow','auto');
	$('.drop-zone .drop-list').hide();
	$('.drop-zone .drop-text').show();

	$.ajax({
		type: 'post',
		url: $('#website_url').val() + 'backend/backend_page/organize/',
		data: {act: 'save', ordered: ordered},
		dataType: 'json',
		beforeSend: function () {
			showSpinner();
		},
		success: function (response) {
			hideSpinner();
			showMessage(response.responseText, response.error);
		}
	})
}
