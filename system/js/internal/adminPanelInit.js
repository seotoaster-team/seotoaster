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

		});
//		$(delPage).css({color:'lavender'}).html('<h2 style="color:lavender;">Are you sure you want to delete this page?</h2><br/> This operation will delete all data asosiated with this page, such as: containers, 301 redirects, deeplinks. Page also will be removed from all featured areas.');
//		$(delPage).dialog({
//			modal: true,
//			title: 'Delete this page',
//			resizable: false,
//			buttons: {
//				'Delete this page': function() {
//					$.ajax({
//						url        : websiteUrl + 'backend/backend_page/delete',
//						type       : 'post',
//						dataType   : 'json',
//						data       : {
//							id : pageId
//						},
//						beforeSend : function() {
//							$(delPage).html('Removing page...');
//						},
//						success : function(response) {
//							if(!response.error) {
//								top.location.href = websiteUrl;
//							}
//							else {
//								$(delPage).dialog('close');
//								showModalMessage(response.responseText.title, response.responseText.body, function() {
//									$(delPage).dialog('close');
//								});
//							}
//
//						}
//					})
//				},
//				Cancel: function() {
//					$( this ).dialog( "close" );
//				}
//			}
//		}).parent().css({background : 'indianred'});
	})

})