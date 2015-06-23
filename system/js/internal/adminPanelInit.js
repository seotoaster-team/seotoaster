$(function () {
    // for IE8
    if(!document.getElementsByClassName) {
        document.getElementsByClassName = function(className) {
            return this.querySelectorAll("." + className);
        };
        Element.prototype.getElementsByClassName = document.getElementsByClassName;
    }

    var panel = document.getElementsByClassName('seotoaster-panel')[0],
        $sectionList = $(document.getElementsByClassName('section-list')[0]),
        section, sectionIndx;

    if (localStorage.getItem('panel-section-active') != null) {
        $sectionList.find('.section:eq(' + localStorage.getItem('panel-section-active') + ')').addClass('active');
    }

    $sectionList.on('click', '.section-btn:not(#cleancache)', function () {
        section = this.parentNode;
        sectionIndx = $(section).index();
        if ($(section).hasClass('active')) {
            $(section).removeClass('active');
            localStorage.removeItem('panel-section-active');
        } else {
            $('.section').removeClass('active');
            localStorage.setItem('panel-section-active', sectionIndx);
            $(section).addClass('active');
        }
    });

    // hide and show aside panel
    $('.show-hide').on('click', function () {
        $(panel).toggleClass('s h');
        $(this).toggleClass('_i-panel-hide _i-panel-show');
    });


    // close section of panel when we click without panel
    $(document).on('click', function (event) {
        if (!$(panel).is(event.target) && $(panel).has(event.target).length === 0) {
            $('.section').removeClass('active');
            localStorage.removeItem('panel-section-active');
        }
    });

    // get CMS version for the notices box
    $.get($('#website_url').val() + '/backend/backend_update/version/', function (response) {
        if (!response.error && response.responseText.status == 1) {
            $('._i-notifications').addClass('new-notices');
            $('.notice._new-version').addClass('show');
        }
    });

    //admin panel edit 404 page click
    $('#edit404').click(function () {
        $.get($('#website_url').val() + '/backend/backend_page/edit404page', function (responseText) {
            console.log(responseText);
            if (responseText.notFoundUrl) {
                window.location.href = responseText.notFoundUrl;
            }
            else {
                smoke.alert($('.edit404-msg').html(), {'classname': 'errors'});
            }
        });
    });

    // Clean all cache
    $('#cleancache').on('click', function () {
        var $this = $(this);
        $this.removeClass('ticon-loop');
        $this.addClass('run ticon-loading');
        //showMessage('Clearing cache...', false);

        $.get($('#website_url').val() + 'backend/backend_content/cleancache/', function (response) {
            if (response.error == 0) {
                showMessage(response.responseText, false, 2500);
                $this.removeClass('run ticon-loading').addClass('ticon-loop');
            }
            else {
                showMessage(response.responseText, true);
                $this.removeClass('run ticon-loading').addClass('ticon-loop');
            }
        });
    });

    //admin panel delete this page link
    $('#del-this-page').click(function () {
        var pageId = $('#del-page-id').val();
        var websiteUrl = $('#website_url').val();

        var isCategory = !!($(this).data('cid') == 0);
        if (isCategory) {
            $.getJSON(websiteUrl + 'backend/backend_page/checkforsubpages/pid/' + pageId, function (response) {
                if (response.responseText.subpages) {
                    smoke.alert(response.responseText.message, function (e) {
                    }, {'classname': 'warning'});
                    return false;
                } else {
                    showDelConfirm();
                }
            });
        } else {
            showDelConfirm();
        }
    })
});

function showDelConfirm() {
    var pageId = $('#del-page-id').val();
    var websiteUrl = $('#website_url').val();
    smoke.confirm('Are you sure you want to delete this page?', function (e) {
        if (e) {
            $.ajax({
                url: websiteUrl + 'backend/backend_page/delete/' + 'id/' + pageId,
                type: 'DELETE',
                dataType: 'json',
                beforeSend: function () {
                    smoke.signal('Removing page...', 30000);
                },
                success: function (response) {
                    hideSpinner();
                    if (!response.error) {
                        top.location.href = websiteUrl;
                    }
                    else {
                        smoke.alert(response.responseText.body, {'classname': 'error'});
                    }

                }
            })
        }
    }, {'classname': 'error', 'ok': 'Yes', 'cancel': 'No'});
}
