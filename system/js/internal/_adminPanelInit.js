$(function() {
	$('#menu2').accordion({
		autoHeight: false,
		navigation: false,
		clearStyle: true,
		icons: false
	})

	$('#showhide').click(function() {
		$('#admincpanel').toggle('slow');
	})
});