$(function() {

	if($.cookie('hideAdminPanel') == null) {
		$.cookie('hideAdminPanel', 0);
	}

	//seotoaster admin panel cookie
	if($.cookie('hideAdminPanel') && $.cookie('hideAdminPanel') == 1) {
		$('#cpanelul').hide();
		$('#logoutul').hide()
		$('#seotoaster-logowrap').hide()
		$('#showhide > a').text('Expand menu').addClass('rounded-bottom');
	}


	$('#cpanelul').accordion({
		autoHeight: false,
		navigation: false,
		clearStyle: true,
		icons: false
	})

	$('#showhide > a').click(function() {
		$.cookie('hideAdminPanel', ($.cookie('hideAdminPanel') == 1) ? 0 : 1);
		$(this).text(($.cookie('hideAdminPanel') == 1) ? 'Expand menu' : 'Collapse menu').toggleClass('rounded-bottom');
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
				showModalMessage('404 page information', 'Sorry, but you don\'t have the 404 error page You can create a page and assign it as 404 error page Use the checkbox on the create/update page screen.', false, true);
			}
		});
	});

	//admin panel delete this page link
	$('#del-this-page').click(function(){
		var delPage    = document.createElement('div');
		var pageId     = $('#del-page-id').val();
		var websiteUrl = $('#website_url').val();
		smoke.confirm('Are you sure you want to delete this page?', function(e) {
			if(e) {
				$.ajax({
					url        : websiteUrl + 'backend/backend_page/delete/',
					type       : 'post',
					dataType   : 'json',
					data       : {
						id : pageId
					},
					beforeSend : function() {
						smoke.signal('Removing page...', 30000);
					},
					success : function(response) {
						if(!response.error) {
							top.location.href = websiteUrl;
						}
						else {
							$(delPage).dialog('close');
							smoke.alert(response.responseText.body, {'classname':'errors'});
						}

					}
				})
			}
		});
		$('div.smoke').css({backgroundColor:'indianred'});
	})
});