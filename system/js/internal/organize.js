$(function() {

	$('#sortable-main').sortable({
		handle : 'div.move',
		stop: saveCategoriesOrder
	});

	$('.organise').sortable({
		connectWith : '.organise'
	});

	$('.collapse-all').click(function() {
		$(this).parent().parent().next().slideToggle();
	})

	$('.check-all').click(function() {
		$(this).parent().next().find('input:checkbox').each(function() {
			$(this).attr('checked', true);
		})
	})
})


function saveCategoriesOrder() {
	var ordered = [];
	$('#sortable-main').find('.category-data').each(function() {
		ordered.push($(this).attr('id'));
	});

	$.ajax({
		type: 'post',
		url : $('#website_url').val() + 'backend/backend_page/organize/',
		data: {ordered: ordered},
		success: function(response) {

		}
	})
}

function savePagesOrder() {

}