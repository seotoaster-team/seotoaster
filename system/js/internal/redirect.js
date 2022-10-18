$(function() {
	$('#urlType-label, #to-url-label').hide();
	reloadRedirectsList();
	var toUrlDropDown = $('#to-url');
	$('#urlType').click(function() {
		if($(this).prop('checked')){
            $('#to_url_chosen').show();
			$('#to-url').replaceWith(toUrlDropDown);
		}else{
			$('#to_url_chosen').hide();
			$('#to-url').replaceWith('<input type="text" id="to-url" name="toUrl" value="http://" />');
		}
	});

	$('.redirect-massdel').on('click', function() {
		if(!$('.redirect-massdel').not(':checked').length) {
			$('#massdell-main').attr('checked', true);
		}
		else {
			$('#massdell-main').attr('checked', false);
		}
	});

	$('#massdell-main').click(function() {
		$('.redirect-massdel').prop('checked', ($(this).prop('checked')) ? true : false);
	});

	$('#massdel-run').click(function() {
		var ids = [];
		$('.redirect-massdel:checked').each(function() {
			ids.push($(this).attr('id'));
		});
		if(!ids.length) {
			showMessage('Select at least one item, please', true);
			return false;
		}
		showConfirm('You are about to remove one or many redirects. Are you sure?', function() {
			var callback = $('#frm-redirects').data('callback');
			$.ajax({
				url: $('#website_url').val() + 'backend/backend_seo/removeredirect/id/'+ids.join(','),
				type: 'DELETE',
				dataType: 'json',
				beforeSend: function() {showSpinner();},
				success: function(response) {
					hideSpinner();
					showMessage(response.responseText, response.error);
					if(typeof callback != 'undefined') {
						eval(callback + '()');
					}
				}
			});
		});
	})
});

$(document).ready(function() {
    $('#to-url').chosen({search_contains: true});

    $('#frm-redirects-search').submit(function(e) {
        e.preventDefault();
        var name = $('#redirect-search').val();
        showSpinner();
        $.ajax({
            url: $('#website_url').val() + 'backend/backend_seo/loadredirectslist/searchName/' + name,
            type: 'post',
            dataType: 'json',
            success: function (response) {
                hideSpinner();
				if(response.redirects.length) {
					$('.seo-paginator').show();
				} else {
					$('.seo-paginator').hide();
				}

                $('#redirects-box').find('#redirects-list').html(response.redirectsList);
            }
        });
    });

    $(document).on('click', '.clear-btn', function () {
        $('#redirect-search').val('');
        $('#frm-redirects-search').trigger('submit');
    });

    $('#redirects-box').append($('.paginator'));
    var count = $('.paginator').length;
    var paginator = $('.not-mutch-paginator').length;
    if(count > 1 || paginator == 1){
        $('.paginator:last').remove();
    }
});

//callback function for the ajax forms
function reloadRedirectsList() {
	$( "input[name='redirect-search']" ).text('');
	var name = $('#redirect-search').val();
	showSpinner();
	$.getJSON($('#website_url').val() + 'backend/backend_seo/loadredirectslist/searchName/'+name, function(response) {
		hideSpinner();
		$('#redirects-list').html(response.redirectsList);
		checkboxRadioStyle();
	});
}
