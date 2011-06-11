$(function() {

	if($.cookie('hideAdminPanel') == null) {
		$.cookie('hideAdminPanel', 0);
	}

	//seotoaster admin panel cookie
	if($.cookie('hideAdminPanel') && $.cookie('hideAdminPanel') == 1) {
		$('#cpanelul').hide();
		$('#logoutul').hide()
		$('#seotoaster-logowrap').hide()
	}

	$('#cpanelul').accordion({
		autoHeight: false,
		navigation: false,
		clearStyle: true,
		icons: false
	})

	$('#showhide > a').click(function() {
		$.cookie('hideAdminPanel', ($.cookie('hideAdminPanel') == 1) ? 0 : 1);
		$('#cpanelul').slideToggle();
		$('#logoutul').toggle();
		$('#seotoaster-logowrap').slideToggle();
	})

	//admin panel edit 404 page click
	$('#edit404').click(function(){
		$.get($('#website_url').val() + 'backend/backend_page/edit404page', function(responseText){
			if(responseText.notFoundUrl) {
				window.location.href = responseText.notFoundUrl;
			}
			else {
				var tmpDiv = document.createElement('div');
				$(tmpDiv).html('Sorry, but you don\'t have the 404 error page. You can create a page and assign it as 404 error page Use the checkbox on the create/update page screen.');
				$(tmpDiv).dialog({
					modal: true,
					title: '404 Page',
					resizable: false,
					buttons: {
					Ok: function() {
						$( this ).dialog( "close" );
					}
			}
				});
			}
		});
	});

	//admin panel delete this page link
	$('#del-this-page').click(function(){
		var delPage    = document.createElement('div');
		var pageId     = $('#del-page-id').val();
		var websiteUrl = $('#website_url').val();
		$(delPage).html('<h2>Are you sure you want to delete this page?</h2><br/> This operation will delete all data asosiated with this page, such as: containers, 301 redirects, etc...');
		$(delPage).dialog({
			modal: true,
			title: 'Delete this page',
			resizable: false,
			buttons: {
				'Delete this page': function() {
					$.ajax({
						url        : websiteUrl + 'backend/backend_page/delete',
						type       : 'post',
						dataType   : 'json',
						data       : {
							id : pageId
						},
						beforeSend : function() {
							$(delPage).html('Removing page...');
						},
						success : function() {
							//$( this ).dialog( "close" );
							top.location.href = websiteUrl;
						}
					})
				},
				Cancel: function() {
					$( this ).dialog( "close" );
				}
			}
		});
	})

})