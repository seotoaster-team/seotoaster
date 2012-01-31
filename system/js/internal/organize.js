$(function() {
	$('#mass-del').click(function() {
		var pagesIds = [];
		$('.page-remove:checked').each(function() {
			pagesIds.push($(this).parent().attr('id'));
		});
		if(!pagesIds.length) {
			showMessage('Select at least one item please', true);
			return;
		}
		showConfirm('You are about to remove one or many pages. Are you sure?', function() {
			$.ajax({
				url: $('#website_url').val() + 'backend/backend_page/delete/',
				type: 'post',
				data: {
					id: pagesIds
				},
				dataType: 'json',
				beforeSend: function() {showSpinner();},
				success: function(response) {
					hideSpinner();
					showMessage(response.responseText);
					$(pagesIds).each(function() {
						$('#' + this).remove();
					})
				}
			});
		});
	});

	$('#sortable-main').sortable({
		handle  : 'div.move',
		cancel  : '.nosort',
		helper  : 'clone',
		stop    : saveCategoriesOrder
	});

	$('.organise').sortable({
		connectWith : '.organise',
		receive: function() {
			var pages = [];
			$(this).find('li').each(function() {
				pages.push($(this).attr('id'));
			});
			renewedData = {
				act        : 'renew',
				menu       : $(this).parent().data('menu'),
				categoryId : $(this).parent().attr('id'),
				pages      : pages
			}
			$.post($('#website_url').val() + 'backend/backend_page/organize/', renewedData)
		},
		stop: saveCategoriesOrder
	});


	$('.collapse-all').click(function() {
		$(this).parent().parent().next().slideToggle();
		if($(this).text() == '-') {
			$(this).text('+');
		}
		else {
			$(this).text('-');
		}
	})

	$('#collapse-global').click(function() {
		$('.organise').slideUp();
		$('.collapse-all').text('+');
	})

	$('#expand-global').click(function() {
		$('.organise').slideDown();
		$('.collapse-all').text('-');
	})

	$('.check-all').click(function() {
		$(this).parent().next().find('input:checkbox').each(function() {
			$(this).attr('checked', true);
		})
	})
})


function saveCategoriesOrder() {
	var rankins = 0;
	var ordered = [];
	$('#sortable-main').find('.category-data').each(function() {
		$(this).find('.ranking').html('#' + ++rankins);
		ordered.push($(this).attr('id'));
		$(this).find('.organise li').each(function() {
			ordered.push($(this).attr('id'));
		})
	});

	$.ajax({
		type: 'post',
		url : $('#website_url').val() + 'backend/backend_page/organize/',
		data: {act: 'save', ordered: ordered},
		dataType: 'json',
		beforeSend: function() {
			showSpinner();
		},
		success: function(response) {
			hideSpinner();
			showMessage(response.responseText, response.error);
		}
	})
}
