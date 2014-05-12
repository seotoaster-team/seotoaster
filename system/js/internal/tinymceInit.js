$(document).ready(function(){
    var websiteUrl = $('#website_url').val();

    tinymce.init({
        script_url              : websiteUrl+'system/js/external/tinymce/tinymce.gzip.php',
        selector                : "textarea.tinymce",
        skin                    : 'seotoaster',
        width                   : '100%',
        height                  : 330,
        menubar                 : false,
        resize                  : false,
        convert_urls            : false,
        relative_urls           : false,
        content_css             : $('#content_css').val(),
        plugins                 : [
            "advlist lists link image charmap",
            "visualblocks code fullscreen",
            "media table paste importcss textcolor stw"
        ],
        importcss_merge_classes : true,
        toolbar1                 : "stw | styleselect | formatselect | fontsizeselect | "+
                                   "pastetext removeformat | visualblocks code fullscreen",
        toolbar2                 : "bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist | " +
                                   "forecolor backcolor | link image media table | hr charmap",
        fontsize_formats        : "8px 10px 12px 14px 18px 24px 36px",
        block_formats           : "Block=div;Paragraph=p;Block Quote=blockquote;Address=address;Code=code;Preformatted=pre;H2=h2;H3=h3;H4=h4;H5=h5;H6=h6"
    });

//    $('textarea.tinymce').tinymce({
//        // general settings
//        script_url                        : websiteUrl+'system/js/external/tinymce/tiny_mce_gzip.php',
//        theme                             : 'advanced',
//        width                             : '100%',
//        height                            : 425,
//        plugins                           : 'preview,fullscreen,media,paste,stw',
//        convert_urls                      : false,
//        relative_urls                     : false,
//        entity_encoding                   : 'raw',
//        content_css                       : $('#content_css').val(),
//        external_link_list_url            : websiteUrl+'backend/backend_page/linkslist/',
//        forced_root_block                 : 'p',
//        valid_elements                    : '*[*]',
//        extended_valid_elements           : 'img[class|src|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name|style]',
//
//        // buttons
//        theme_advanced_buttons1           : 'bold,italic,underline,|,justifyleft,justifycenter,justifyright,justifyfull,bullist,numlist,styleselect,formatselect,fontsizeselect,forecolor,backcolor,link,unlink',
//        theme_advanced_buttons2           : 'image,|,widgets,|,pastetext,removeformat,charmap,hr,table',
//
//        // theme advanced related settings
//        theme_advanced_blockformats       : 'div,p,blockquote,address,code,pre,h2,h3,h4,h5,h6',
//        theme_advanced_toolbar_location   : 'top',
//        theme_advanced_toolbar_align      : 'left',
//        theme_advanced_resizing           : false,
//        theme_advanced_statusbar_location : 'none',
//
//        // setup hook
//        setup                             : function(ed){
//            ed.keyUpTimer = null;
//            ed.onKeyUp.add(function(ed, e){
//                //@see content.js for this function
//                dispatchEditorKeyup(ed, e);
//            });
//        }
//    });
});
