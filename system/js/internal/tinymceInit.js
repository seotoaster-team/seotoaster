$(document).ready(function(){
    var websiteUrl = $('#website_url').val();

    tinymce.init({
        script_url              : websiteUrl+'system/js/external/tinymce/tinymce.gzip.php',
        selector                : "textarea.tinymce",
        skin                    : 'seotoaster',
        width                   : '100%',
        menubar                 : false,
        resize                  : false,
        convert_urls            : false,
        relative_urls           : false,
        statusbar               : false,
        allow_script_urls       : true,
        content_css             : $('#reset_css').val()+','+$('#content_css').val(),
        importcss_file_filter   : "content.css",
        importcss_groups : [
            {title : 'Button styles', filter : /^(.btn*|button\.)/},
            {title : 'Table styles', filter : /^(.table*|table\.|tr\.|td\.|th\.)/},
            {title : 'List styles', filter : /^(.list*|ul\.|ol\.)/},
            {title : 'Image styles', filter : /^(.image*|img\.)/},
            {title : 'Block quote styles', filter : /^(blockquote\.)/},
            {title : 'Separator styles', filter : /^(hr\.)/},
            {title : 'Message styles', filter : /^(\.message*)/},
            {title : 'Badge styles', filter : /^(\.badge*)/},
            {title : 'Primary colors', filter : /^(\.primary*|\.success*|\.info*|\.warning*|\.error*|\.green*|\.blue*|\.orange*|\.red*|\.color*)/},
            {title : 'Size classes', filter : /^(\.larger*|\.large*|\.small*|\.mini*|\.size*)/},
            {title : 'Other styles'}
        ],
        importcss_merge_classes: true,
        plugins                 : [
            "advlist lists link anchor image charmap", "visualblocks code fullscreen", "media table paste importcss textcolor stw"
        ],
        toolbar1                : "bold italic underline alignleft aligncenter alignright alignjustify | bullist numlist forecolor backcolor | link unlink anchor image media table hr",
        toolbar2                : "stw | styleselect | formatselect | fontsizeselect | pastetext visualblocks code removeformat | fullscreen",
        fontsize_formats        : "8px 10px 12px 14px 16px 18px 24px 36px",
        block_formats           : "Block=div;Paragraph=p;Block Quote=blockquote;Cite=cite;Address=address;Code=code;Preformatted=pre;H2=h2;H3=h3;H4=h4;H5=h5;H6=h6",
        link_list               : websiteUrl+'backend/backend_page/linkslist/',
        image_advtab            : true,
        extended_valid_elements : "a[accesskey|charset|class|coords|dir<ltr?rtl|href|hreflang|id|lang|name"
                                +"|onblur|onclick|ondblclick|onfocus|onkeydown|onkeypress|onkeyup"
                                +"|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|rel|rev|"
                                +"style|tabindex|title|target|type]",
        setup                   : function(ed){
            var keyTime = null;
            ed.on('change blur keyup', function(ed, e){
                //@see content.js for this function
                dispatchEditorKeyup(ed, e, keyTime);
                this.save();
            });
        }
    });
});
