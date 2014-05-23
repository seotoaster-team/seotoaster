$(document).on("dblclick", '.content-editable', function(){
});

function initContent(url){
    var websiteUrl = $('#website_url').val();

    tinymce.init({
        selector                : ".content-editable",
        inline                  : true,
        skin                    : 'seotoaster',
        menubar                 : false,
        convert_urls            : false,
        relative_urls           : false,
        script_url              : websiteUrl+'system/js/external/tinymce/tinymce.gzip.php',
//        content_css             : $('#reset_css').val()+','+$('#content_css').val(),
//        importcss_file_filter   : "content.css",
//        importcss_groups        : [
//            {title : 'Button styles', filter : /^(.btn*|button\.)/},
//            {title : 'Table styles', filter : /^(.table*|table\.|tr\.|td\.|th\.)/},
//            {title : 'List styles', filter : /^(.list*|ul\.|ol\.)/},
//            {title : 'Image styles', filter : /^(.image*|img\.)/},
//            {title : 'Block quote styles', filter : /^(blockquote\.)/},
//            {title : 'Primary colors', filter : /^(\.primary*|\.green|\.blue|\.orange|\.red|\.color*)/},
//            {title : 'Size classes', filter : /^(\.larger|\.large|\.small|\.mini|\.size*)/},
//            {title : 'Other styles'}
//        ],
//        importcss_merge_classes : true,
        plugins                 : [
            "advlist lists link image charmap", "visualblocks code fullscreen", "media table paste importcss textcolor stw save"
        ],
        importcss_merge_classes : true,
        toolbar1                : "bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist | forecolor backcolor | link image media table | pastetext removeformat",
        toolbar2                : "stw | styleselect | formatselect | fontsizeselect | hr charmap | visualblocks code fullscreen | save",
        fontsize_formats        : "8px 10px 12px 14px 18px 24px 36px",
        block_formats           : "Block=div;Paragraph=p;Block Quote=blockquote;Address=address;Code=code;Preformatted=pre;H2=h2;H3=h3;H4=h4;H5=h5;H6=h6",
        link_list               : websiteUrl+'backend/backend_page/linkslist/',
        image_advtab            : true,
        save_enablewhendirty: true,
        save_onsavecallback: function() {
            console.log("Save");
        },
        setup                   : function(ed){
            ed.on("blur", function(){
            });
        }
    });
}

function initHeader(){
    tinymce.init({
        selector : ".header-editable",
        inline   : true,
        toolbar  : false,
        menubar  : false
    });
}
