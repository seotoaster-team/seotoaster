$(function() {
    $('#mass-del').click(function() {
        var pagesIds = [];
        $('.page-remove:checked').each(function() {
            pagesIds.push($(this).closest('li').attr('id'));
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

    $('.move').each(function(){
        $(this).click(function(e) {
            if(e.ctrlKey) {
                $(this).toggleClass('selected');
                if($(this).find("input[type='checkbox']").prop("checked")){
                    $(this).find("input[type='checkbox']").prop("checked", false);
                } else{
                    $(this).find("input[type='checkbox']").prop("checked", true);
                }

            }
        });
    });
    $('.organise').on('click', '.page-remove', function(e){
        e.stopImmediatePropagation();
        $(this).closest('.move').toggleClass('selected');
    });

    $('.organise').each(function(){
        $(this).sortable({
            connectWith : '.organise',
            placeholder: 'placeholder',
            start: function(ui,e) {
                var selected = e.item.siblings(".selected");
                var item = e.item;
                selected.appendTo(item);
            },
            receive: function(ui,e){
                var pages = [];
                $(this).find('li').each(function() {
                    pages.push($(this).attr('id'));
                });
                renewedData = {
                    act        : 'renew',
                    menu       : $(this).parent().data('menu'),
                    categoryId : $(this).parent().attr('id'),
                    pages      : pages
                };
                $.post($('#website_url').val() + 'backend/backend_page/organize/', renewedData);
            },
            stop: function(ui, e){
                var finded = e.item.find(".selected");
                e.item.after(finded);
                saveCategoriesOrder();
            }
        });
    });



    $('.collapse-all').click(function() {
        $(this).parent().next().slideToggle();
        $(this).toggleClass('ticon-arrow-up ticon-arrow-down');
    });

    $('#collapse-global').click(function() {
        $('.organise').slideUp();
        $('.collapse-all').removeClass('ticon-arrow-up').addClass('ticon-arrow-down');
    });

    $('#expand-global').click(function() {
        $('.organise').slideDown();
        $('.collapse-all').removeClass('ticon-arrow-down').addClass('ticon-arrow-up');
    });

    $('.check-all').click(function() {
        $(this).closest('.catmove').next().find('input:checkbox').prop('checked', $(this).prop('checked'));
    });
});


function saveCategoriesOrder() {
    var rankins = 0;
    var ordered = [];
    $('#sortable-main').find('.category-data').each(function() {
        $(this).find('.ranking').html('#' + ++rankins);
        ordered.push($(this).attr('id'));
        $(this).find('.organise li').each(function() {
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
    if(elements !== 'undefined'){
        if(elements.length > 1){
            $.each(elements,function(key,el){
                $(el).removeClass("selected");
                $(el).find("input[type='checkbox']").attr("checked", false);
            })
        } else {
            $(elements).removeClass("selected");
            $(elements).find("input[type='checkbox']").attr("checked", false);
        }
    }
}
