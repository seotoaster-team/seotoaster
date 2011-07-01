$(function() {

	$('#sortable-main').sortable({
		handle : 'div.move',
		cancel : '.nosort',
		stop   : saveCategoriesOrder
	});

	$('.organise').sortable({
		connectWith : '.organise',
		receive: function() {
			var pages = [];
			$(this).find('li').each(function() {
				pages.push($(this).attr('id'));
			})
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
	})

	$('#collapse-global').click(function() {
		$('.organise').slideUp();
	})

	$('#expand-global').click(function() {
		$('.organise').slideDown();
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
		success: function(response) {

		}
	})
}
