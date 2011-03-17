$(document).ready(function(){
	
	//compressor
	tinyMCE_GZ.init({
		plugins : 'safari,preview,spellchecker,media',
		themes : 'simple,advanced',
		languages : 'en',
		disk_cache : true,
		debug : false
	});

	//seotoaster plugin for the custom widgets drop-down
	tinymce.create('tinymce.plugins.SeotoasterWidgets', {
		createControl: function(n, cm) {
			switch (n) {
				case 'widgets':
					var widgetList = cm.createListBox('widgets', {
						title: 'Widgets',
						onselect: function(v) {
							$('textarea.tinymce').tinymce().execCommand('mceInsertContent', false, v)
						}
					});
					$.ajax({
						type: 'post',
						url: '/backend/backend_content/loadwidgets/',
						success: function(widgets) {
							for(var i = 0; i < widgets.length; i++) {
								for(var j = 0; j < widgets[i].length; j++) {
									widgetList.add('{$' + widgets[i][j] + '}', '{$' + widgets[i][j] + '}');
								}
							}
						},
						dataType: 'json'
					});
					return widgetList;
				break;
			}
			return null;
		}
	})

	tinymce.PluginManager.add('stw', tinymce.plugins.SeotoasterWidgets);

	//init of tinymce
	$('textarea.tinymce').tinymce({
		//script_url: '/system/js/external/tinymce/tiny_mce.js',
		theme : "advanced",
		elements : 'nourlconvert',
		width: 620,
		height: 506,
		plugins : "safari,preview,spellchecker,fullscreen,media,paste,-stw",
		//plugins : "pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,advlist",
		theme_advanced_buttons1 : "bold,italic,underline,|,justifyleft,justifycenter,justifyright,justifyfull,bullist,numlist,formatselect,styleselect,fontsizeselect,forecolor,|,link,unlink,anchor,hr",
		theme_advanced_buttons2 : "pastetext,charmap,image,media,|,widgets,|,fullscreen,preview,spellchecker,|,undo,redo,|,removeformat,cleanup,code",
		theme_advanced_buttons3 : "",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : false,
		convert_urls: 0,
        entity_encoding : "raw",
		paste_auto_cleanup_on_paste : true,
		paste_remove_styles: true,
		content_css: $('#website_url').val() + '/themes/' + $('#current_theme').val() + '/content.css',
		disk_cache : true,
		debug : false
	})
})

