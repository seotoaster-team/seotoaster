$(document).ready(function() {
	$('#frm_template').submit(saveTemplate);
})

function saveTemplate() {
	var ajaxMsgSuccess = $('#ajax_msg');
	var ajaxMsgFail    = $('#ajax_msg_fail');
	$.ajax({
		url        : $(this).attr('action'),
		type       : 'post',
		dataType   : 'json',
		data       : $(this).serialize(),
		beforeSend : function() {
			ajaxMsgSuccess.fadeIn();
		},
		success : function(response) {
			ajaxMsgSuccess.html('Template saved');
			console.log(response);
			//top.location.reload();
		},
		error: function() {
			ajaxMsgSuccess.hide();
			ajaxMsgFail.show();
		}
	})
	return false;
}



