$(function(){
    var websiteUrl = $('#website_url').val(),
        toolbar = 'bold italic underline lineheight alignleft aligncenter alignright alignjustify bullist numlist forecolor backcolor link unlink anchor image media table hr styleselect formatselect fontsizeselect pastetext visualblocks removeformat wordcount searchreplace codesample code fullscreen stw darkmode ',
        showMoreFlag = $('.show-more-content-widget').length;

    if(showMoreFlag){
        toolbar += ' showMoreButton';
    }

    tinymce.init({
        selector : "textarea.tinymce",
        skin: 'oxide',//'seotoaster'
        width  : '100%',//'608px',
        height : '450px',
        menubar: false,
        resize: false,
        convert_urls: false,
        browser_spellcheck: true,
        relative_urls: false,
        statusbar: false,
        allow_script_urls: true,
        force_p_newlines: false,
        force_br_newlines : true,
        forced_root_block: '',
        remove_linebreaks : false,
        convert_newlines_to_br: true,
        entity_encoding: "raw",
        plugins: [
            "imagetools wordcount searchreplace importcss advlist lists autolink link anchor image charmap visualblocks codesample code fullscreen media table paste hr quickbars stw"
        ],
        toolbar1 : toolbar,
        codesample_languages: [
            { text: 'HTML/XML', value: 'markup' },
            { text: 'JavaScript', value: 'javascript' },
            { text: 'CSS', value: 'css' },
            { text: 'PHP', value: 'php' },
            { text: 'Ruby', value: 'ruby' },
            { text: 'Python', value: 'python' },
            { text: 'Java', value: 'java' },
            { text: 'C', value: 'c' },
            { text: 'C#', value: 'csharp' },
            { text: 'C++', value: 'cpp' }
        ],
        default_link_target: '_blank',
        toolbar_sticky: true,
        link_list               : websiteUrl+'backend/backend_page/linkslist/',
        //content_css             : $('#reset_css').val()+','+$('#content_css').val(),
        content_css             : $('#content_css').val()+ ','+ 'default',
        importcss_file_filter   : "/content.css",
        importcss_append: true,
        importcss_selector_filter: /^(?!\.h1|\.h2|\.h3|\.h4|\.h5|\.h6|\.social-links*|\.callout*|\.callout*|\.panel*|.icon-*|\.icon12|\.icon14|\.icon16|\.icon18|\.icon24|\.icon32|\.icon48|\.toaster-icon|hr\.)/,
        importcss_groups : [
            {title : 'h1', filter : /^(h1\.)/},
            {title : 'h2', filter : /^(h2\.)/},
            {title : 'h3', filter : /^(h3\.)/},
            {title : 'h4', filter : /^(h4\.)/},
            {title : 'h5', filter : /^(h5\.)/},
            {title : 'h6', filter : /^(h6\.)/},
            {title : 'Button', filter : /^(\.btn.*|button\.)/},
            {title : 'Icons', filter : /^(\.no-icon|\.icon-.*)/},
            {title : 'Table', filter : /^(\.table.*|table\.)/},
            {title : 'List', filter : /^(\.list.*|ul\.|ol\.)/},
            {title : 'Image', filter : /^(\.image.*|\.img.*|img\.)/},
            {title : 'Blockquote', filter : /^(blockquote\.)/},
            {title : 'Separator', filter : /^(hr\.)/},
            {title : 'Message', filter : /^(\.message.*)/},
            {title : 'Badge', filter : /^(\.badge.*)/},
            {title : 'Color', filter : /^(\.primary|\.success|\.info|\.warning|\.error|\.green|\.blue|\.orange|\.red|\.gray-darker|\.gray-dark|\.gray|\.gray-light|\.gray-lighter|\..*color.*)$/},
            {title : 'Background', filter : /^(\..*-bg.*)/},      //new group
            {title : 'Size', filter : /^(\.larger|\.large|\.small|\.mini|\.size.*|\.fs.*)$/},
            {title : 'Text', filter : /^(\.uppercase|\.lowecase)$/},
            {title : 'Other styles'}
        ],
        importcss_merge_classes: true,
        //quickbars_selection_toolbar: 'bold italic | quicklink h2 h3 blockquote quickimage quicktable',
        quickbars_selection_toolbar: false,
        fontsize_formats        : "8px 10px 12px 14px 16px 18px 24px 36px",
        block_formats: "Block=div;Paragraph=p;Address=address;Code=code;Preformatted=pre;H2=h2;H3=h3;H4=h4;H5=h5;H6=h6;",//Block Quote=blockquote;
        extended_valid_elements: "a[*],input[*],select[*],textarea[*]",
        image_advtab: true,
        setup : function(ed){
            var keyTime = null;
            ed.on('change blur keyup', function(ed, e){
                //@see content.js for this function
                self.dispatchEditorKeyup(ed, e, keyTime);
                this.save();
            });

            ed.ui.registry.addButton('showMoreButton', {
                title:'showMoreButton',
                text: 'Show more widget',
                onAction : function(_) {
                    if(showMoreFlag) {
                        var SHOWMORE = '#show-more#';
                        if (ed.getContent().indexOf(SHOWMORE) + 1) {
                            showMessage('Widget ' + SHOWMORE + ' already exists in content', false, 2000);
                        } else {
                            ed.focus();
                            ed.selection.setContent(SHOWMORE);
                        }
                    }
                }
            });
            ed.on('ExecCommand', function(editor, prop) {
                if (editor.command === 'mceInsertContent') {
                    if(typeof editor.value.content !== 'undefined') {
                        ed.selection.setContent('<span id="cursor-position-temp-span"/>');

                        var urlRegex = /(\b(https?):\/\/[\w\-]*)(?![^<>]*>(?:(?!<\/?a\b).)*<\/a>)/igu,
                            contentDomains = editor.value.content.match(urlRegex),
                            containerContent = tinymce.activeEditor.getContent();

                        if(contentDomains) {
                            var urlToLinkExp = /(\b(https?):\/\/[\w\/#=&;%\-?\.]*)((?![^<>]*>(?:(?!(<\/?a\b|<img\b)).)*))/igu;
                            containerContent = containerContent.replace(urlToLinkExp, function(url) {
                                return '<a href="' + url + '" target="_blank">' + url.replace(/(^\w+:|^)\/\//, '') + '</a>';
                            });

                            tinymce.activeEditor.setContent(containerContent);
                        }

                        var newNode = ed.dom.select('span#cursor-position-temp-span');
                        ed.selection.select(newNode[0]);
                        ed.selection.setContent('');
                    }
                }

                if (editor.command === 'mceFullScreen') {
                    var popup = $(window.parent.document).find('[aria-describedby="toasterPopupDraggable"]');
                    popup.toggleClass('screen-expand');
                    var $tabs = $('#tabs'),
                        height = $tabs.height(),
                        tabNavHeight = $tabs.find('.ui-tabs-nav').height(),
                        $tabHeader = $tabs.find('#adminthingsviewer .ui-accordion-header'),
                        tabHeaderLenght = $tabHeader.length,
                        tabHeaderHeight = $tabHeader.outerHeight(),
                        tabFolderFieldHeight = $tabs.find('#adminselectimgfolder').outerHeight(),
                        tabProductButton = $tabs.find('#btn-create').outerHeight(),
                        tabNetContentButton = $tabs.find('#widgetSync').outerHeight() + 5;

                    $tabs.find('#adminthingsviewer .ui-accordion-content').css({
                        'max-height' : height - tabNavHeight - (tabHeaderHeight + 2) * tabHeaderLenght  - tabFolderFieldHeight - 30
                    });
                    $tabs.find('#product-products').css({
                        'height' : height - tabNavHeight - tabProductButton - 116
                    });
                    $tabs.find('.netcontent-widget-list').css({
                        'height' : height - tabNavHeight - tabNetContentButton - 12
                    });
                }
            });
            ed.ui.registry.addButton('darkmode', {
                title:'darkmode',
                text: 'Dark mode',
                tooltip: "Don't see white text? click here",
                onAction: function(_) {
                    var tinyContent =  $('#content_ifr').contents().find('body').get(0);

                    if(typeof tinyContent !== 'undefined') {
                        var existStyle = tinyContent.getAttribute('style');

                        if(existStyle) {
                            tinyContent.style = null;
                        } else {
                            tinyContent.style.background = "#2f3742";
                            tinyContent.style.color = "#dfe0e4";
                        }
                    }
                }
            });
        }
    });
});
