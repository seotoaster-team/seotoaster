$(document).ready(function(){
    var websiteUrl = $('#website_url').val();

    $('textarea.tinymce').tinymce({
        // general settings
        script_url             : websiteUrl + 'system/js/external/tinymce/tiny_mce_gzip.php',
        theme                  : 'advanced',
        width                  : '100%',
        height                 : 425,
        plugins                : 'preview,spellchecker,fullscreen,media,paste,stw',
        convert_urls           : false,
        relative_urls          : false,
        entity_encoding        : 'raw',
        content_css            : $('#content_css').val(),
        external_link_list_url : websiteUrl + 'backend/backend_page/linkslist/',
        forced_root_block      : 'p',
        valid_elements         : '*[*]',
        extended_valid_elements: 'img[class|src|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name|style]',

        // buttons
        theme_advanced_buttons1 : 'bold,italic,underline,|,justifyleft,justifycenter,justifyright,justifyfull,bullist,numlist,styleselect,formatselect,fontsizeselect,forecolor,backcolor,link,unlink',
		theme_advanced_buttons2 : 'image,|,widgets,|,pastetext,removeformat,charmap,hr',

        // theme advanced related settings
        theme_advanced_blockformats       : 'div,blockquote,p,address,pre,h2,h3,h4,h5,h6',
		theme_advanced_toolbar_location   : 'top',
		theme_advanced_toolbar_align      : 'left',
        theme_advanced_resizing           : false,
        theme_advanced_statusbar_location : 'none',

        // setup hook
        setup : function(ed) {
			ed.keyUpTimer = null;
			ed.onKeyUp.add(function(ed, e) {
				//@see content.js for this function
				dispatchEditorKeyup(ed, e);
			});
		}
    });
});
