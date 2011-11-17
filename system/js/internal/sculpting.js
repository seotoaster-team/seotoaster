$(function() {

	$('#addSilo-label').hide();

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
		var cid    = $(this).val();
		var actUrl = '';
		$('#ajax_msg').text('Siloing category').show();
		if($(this).prop('checked')) {
			actUrl = $('#website_url').val() + 'backend/backend_seo/silocat/act/add/';
		}
		else {
			actUrl = $('#website_url').val() + 'backend/backend_seo/silocat/act/remove/'
		}
		$.post(actUrl, {
			cid : cid
		}, function() {
			$('#ajax_msg').text('Done').fadeOut();
			loadSculptingData();
		});
	})

	$('a#manage-silos').button();
})

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