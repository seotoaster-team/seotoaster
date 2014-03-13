$(function() {
    $('#mass-del').click(function() {
        var pagesIds = [];
        $('.page-remove:checked').each(function() {
            pagesIds.push($(this).parent().attr('id'));
        });
        if(!pagesIds.length) {
            showMessage('Select at least one item please', true);
            return;
        }
        showConfirm('You are about to remove one or many pages. Are you sure?', function() {
            $.ajax({
                url: $('#website_url').val() + 'backend/backend_page/delete/',
                type: 'post',
                data: {
                    id: pagesIds
                },
                dataType: 'json',
                beforeSend: function() {showSpinner();},
                success: function(response) {
                    hideSpinner();
                    showMessage(response.responseText);
                    $(pagesIds).each(function() {
                        $('#' + this).remove();
                    })
                }
            });
        });
    });

    $('#sortable-main').sortable({
        handle  : '.catmove',
        cancel  : '.nosort',
        helper  : 'clone',
        stop    : saveCategoriesOrder
    });

    $('.organise').on('click', '.page-remove', function(e){
        $(this).parent().parent().toggleClass('selected');
    })

//    $('.organise').on('mousedown', '.move', function(e){
//        e.preventDefault;
//        e.stopPropagation;
//        $(this).addClass('selected');
//        $(this).find("input[type='checkbox']").attr("checked", true);
//    })

    $('.organise').sortable({
        connectWith : '.organise',
        delay: 150, //prevent accidental drag when trying to select
        helper : function(e,item){
            var elements = item.parent().children('.selected').clone();
            removeMarks(elements);
            item.data('multidrag', elements).siblings('.selected').remove();
            var helper = $('<li/>');
            return helper.append(elements);
        },
        receive: function(e,ui) {
            var elements = ui.item.data('multidrag');
            ui.item.after(elements).remove();

            var pages = [];
            $(this).find('li').each(function() {
                pages.push($(this).attr('id'));
            });
            renewedData = {
                act        : 'renew',
                menu       : $(this).parent().data('menu'),
                categoryId : $(this).parent().attr('id'),
                pages      : pages
            }
            $.post($('#website_url').val() + 'backend/backend_page/organize/', renewedData)
        },
        stop: function(e, ui){
            var elements = ui.item.data('multidrag');
            removeMarks(elements);
            ui.item.after(elements).remove();
            saveCategoriesOrder();
        }
    });


    $('.collapse-all').click(function() {
        $(this).parent().next().slideToggle();
        $(this).toggleClass('icon-arrow-up icon-arrow-down');
    })

    $('#collapse-global').click(function() {
        $('.organise').slideUp();
        $('.collapse-all').removeClass('icon-arrow-up').addClass('icon-arrow-down');
    })

    $('#expand-global').click(function() {
        $('.organise').slideDown();
        $('.collapse-all').removeClass('icon-arrow-down').addClass('icon-arrow-up');
    })

    $('.check-all').click(function() {
        $(this).closest('.catmove').next().find('input:checkbox').prop('checked', $(this).prop('checked'));
    })
});


function saveCategoriesOrder() {
    var rankins = 0;
    var ordered = [];
    $('#sortable-main').find('.category-data').each(function() {
        $(this).find('.ranking').html('#' + ++rankins);
        ordered.push($(this).attr('id'));
        $(this).find('.organise li').each(function() {
            if($(this).hasClass('selected')){
                console.log('this.class',$(this).attr("class"));
                $(this).removeClass('selected')
            }
            ordered.push($(this).attr('id'));
        })
    });


        $.ajax({
            type: 'post',
            url : $('#website_url').val() + 'backend/backend_page/organize/',
            data: {act: 'save', ordered: ordered},
            dataType: 'json',
            beforeSend: function() {
                showSpinner();
            },
            success: function(response) {
                hideSpinner();
                showMessage(response.responseText, response.error);
            }
        })


}

function removeMarks(elements){
        if(elements.length !== 'undefined' && elements.length > 1){
            $.each(elements,function(key,el){
                $(el).removeClass("selected");
                $(el).find("input[type='checkbox']").attr("checked", false);
            })
        } else {
            $(elements).removeClass("selected");
            $(elements).find("input[type='checkbox']").attr("checked", false);
        }


}
