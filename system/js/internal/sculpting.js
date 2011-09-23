$(function() {
	loadSculptingData();
	$('.silo-select').live('change', function(){
		var pid = $(this).attr('id');
		var sid = $(this).val();
		$('#ajax_msg').text('Adding page to the silo').show();
		$.post($('#website_url').val() + 'backend/backend_seo/addsilotopage/', {
			pid : pid,
			sid : sid
		}, function() {
			$('#ajax_msg').text('Added').fadeOut();
		});
	})

	$('.silo-this-cat').live('click', function() {
		var cid = $(this).val();
		if($(this).attr('checked')) {
			$('#ajax_msg').text('Siloing category').show();
			$.post($('#website_url').val() + 'backend/backend_seo/silocat/', {
				cid : cid
			}, function() {
				$('#ajax_msg').text('Done').fadeOut();
				loadSculptingData();
			});
		}
		else {
			$('#ajax_msg').text('Unsiloing category').show();
			$.post($('#website_url').val() + 'backend/backend_seo/unsilocat/', {
				cid : cid
			}, function() {
				$('#ajax_msg').text('Done').fadeOut();
				loadSculptingData();
			});
		}
	})
})

runPageRankSculpting = function() {

}

sculptingCallback = function() {
	$('#silo-name').val('');
	loadSculptingData();
}

loadSculptingData = function() {
	$('#sculpting-list').addClass('ajaxspineer');
	$.getJSON($('#website_url').val() + 'backend/backend_seo/loadsculptingdata', function(response) {
		$('#sculpting-list').removeClass('ajaxspineer').html(response.sculptingList);
	})
}