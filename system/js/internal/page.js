$(function() {

	$('#templatelist').delegate('div.template_preview', 'click', function() {
		var templateId = $(this).find('input[name="template-id"]').val();
		$('#templateId').val(templateId);
		$('#curr-template').text(templateId);
		$('#templatelist').slideUp();
	});

	if(!$('#pageId').val()) {
		$('#h1').keyup(function() {
			var currentValue = $(this).val();
			$('#header-title').val(currentValue);
			$('#url').val(currentValue);
			$('#nav-name').val(currentValue);
		})

		
	}

})