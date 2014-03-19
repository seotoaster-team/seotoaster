$(function () {
    $('#addSilo-label').hide();
    loadSculptingData();
    $(document).on('change', '.silo-select', function () {
        var pid = $(this).attr('id');
        var sid = $(this).val();
        showSpinner(this);
        $.post(
            $('#website_url').val() + 'backend/backend_seo/addsilotopage/',
            {
                pid: pid,
                sid: sid
            },
            function (response) {
                hideSpinner();
                showMessage((typeof response.responseText != 'undefined') ? response.responseText : 'Added');
            }
        );
    });

    $(document).on('click', '.silo-this-cat', function () {
        var cid = $(this).val();
        var actUrl = $('#website_url').val();
        showSpinner(this);
        if ($(this).prop('checked')) {
            actUrl += 'backend/backend_seo/silocat/act/add/';
        } else {
            actUrl += 'backend/backend_seo/silocat/act/remove/'
        }
        $.post(
            actUrl,
            {cid: cid},
            function () {
                loadSculptingData();
            }
        );
    })
});

sculptingCallback = function() {
    $('#silo-name').val('');
    loadSculptingData();
};

var loadSculptingData = function () {
    showSpinner();
    $.getJSON($('#website_url').val() + 'backend/backend_seo/loadsculptingdata', function (response) {
        hideSpinner();
        $('#sculpting-list').html(response.sculptingList);
        checkboxRadioStyle();
    })
};