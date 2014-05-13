$(document).ready(function(){
    var websiteUrl = $('#website_url').val();

    tinymce.init({
        script_url              : websiteUrl+'system/js/external/tinymce/tinymce.gzip.php',
        selector                : "textarea.tinymce",
        skin                    : 'seotoaster',
        width                   : '100%',
        height                  : 360,
        menubar                 : false,
        resize                  : false,
        convert_urls            : false,
        relative_urls           : false,
        statusbar               : false,
        content_css             : $('#content_css').val(),
        plugins                 : [
            "advlist lists link image charmap", "visualblocks code fullscreen", "media table paste importcss textcolor stw"
        ],
        importcss_merge_classes : true,
        toolbar1                : "stw | styleselect | formatselect | fontsizeselect | "+"pastetext removeformat | visualblocks code fullscreen",
        toolbar2                : "bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist | "+"forecolor backcolor | link image media table | hr charmap",
        fontsize_formats        : "8px 10px 12px 14px 18px 24px 36px",
        block_formats           : "Block=div;Paragraph=p;Block Quote=blockquote;Address=address;Code=code;Preformatted=pre;H2=h2;H3=h3;H4=h4;H5=h5;H6=h6",
        link_list               : websiteUrl+'backend/backend_page/linkslist/'
    });
});
