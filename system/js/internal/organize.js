$(function() {

	$('#mass-del').button().click(function() {
		var pagesIds = [];
		$('.page-remove:checked').each(function() {
			pagesIds.push($(this).parent().attr('id'));
		})
		if(!pagesIds.length) {
			showModalMessage('Pick a page(s)', 'You have not select any page. Pick at least one.');
			return;
		}
		var messageScreen = $('<div class="info-message"></div>').html('Do you really want to remove selected pages?');
		$(messageScreen).dialog({
			modal    : true,
			title    : 'Removing pages?',
			resizable: false,
			buttons: {
				Yes: function() {
					var url  = $('#website_url').val() + 'backend/backend_page/delete/'
					$.post(url, {id: pagesIds}, function(response) {
						$(pagesIds).each(function() {
							$('#' + this).remove();
						})
						$('#ajax_msg').html(response.responseText).show().fadeOut(_FADE_SLOW);
					}, 'json')
					$(this).dialog('close');
				},
				No : function() {
					$(this).dialog('close');
				}
			}
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
		beforeSend: function() {
			$('#ajax_msg').text('Saving order...').show();
		},
		success: function(response) {
			$('#ajax_msg').text('New order saved...').fadeOut(_FADE_SLOW);
		}
	})
}
