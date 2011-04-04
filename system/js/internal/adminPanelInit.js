$(function() {
	$('#cpanelul').accordion({
		autoHeight: false,
		navigation: false,
		clearStyle: true,
		icons: false
	})

	$('#showhide > a').click(function() {
		$('#cpanelul').slideToggle();
		$('#logoutul').toggle();
		$('#seotoaster-logowrap').slideToggle();
	})
});