$(function(){

    $('input.user-attribute').on('focus', function(e){
        console.log('@TODO create backup');
    });
    $('input.user-attribute').on('keyup', function(e){
        if (e.keyCode == 13){
            $(this).trigger('blur');
            return false;
        }
    });
    $('select.user-attribute').on('change', function(e){
        var data = {},
            uId = $(this).data('uid');
        data[$(this).data('attribute')] = $(this).val();

        sendAjax(data, uId);
    });

    $('input.user-attribute').on('change', function(e){
        var data = {},
            uId = $(this).data('uid');
        data[$(this).data('attribute')] = $(this).val();

        sendAjax(data, uId);
    });

    function sendAjax(data, uId) {
        $.ajax({
            url: $('#website_url').val() + 'api/toaster/users/id/' + uId,
            method: 'PUT',
            data: JSON.stringify(data),
            complete: function(xhr, status, response) {
                if (status === 'error'){
                    showMessage(status, true);
                } else {

                }
            }
        })
    }
});