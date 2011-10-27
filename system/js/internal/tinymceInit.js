$(document).ready(function(){
	//init of tinymce
	var websiteUrl = $('#website_url').val();
	$('textarea.tinymce').tinymce({
		script_url              : websiteUrl + 'system/js/external/tinymce/tiny_mce_gzip.php',
		theme                   : "advanced",
		elements                : 'nourlconvert',
		width                   : 620,
		height                  : 565,
		plugins                 : "safari,preview,spellchecker,fullscreen,media,paste,stw", //table
		//plugins               : "pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,advlist",
		theme_advanced_buttons1 : "bold,italic,underline,|,justifyleft,justifycenter,justifyright,justifyfull,bullist,numlist,styleselect,formatselect,fontsizeselect,forecolor,|,link,unlink,anchor,hr",
		theme_advanced_buttons2 : "image,|,widgets,|,spellchecker,|,code,pastetext,removeformat,charmap,fullscreen", //,tablecontrols
		theme_advanced_buttons3 : "",
		spellchecker_languages  : "+English=en,French=fr,German=de,Hebrew=iw,Italian=it,Polish=pl,Portuguese (Brazil)=pt-BR,"
					              +"Portuguese (Portugal)=pt-PT,Russian=ru,Spanish=es,Ukrainian=uk",
		spellchecker_rpc_url    : websiteUrl + 'system/js/external/tinymce/plugins/spellchecker/rpc.php',
		theme_advanced_blockformats: "p,address,pre,h2,h3,h4,h5,h6",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : false,
		convert_urls: 0,
        entity_encoding : "raw",
		paste_auto_cleanup_on_paste : true,
		paste_remove_styles: true,
		content_css: websiteUrl + '/themes/' + $('#current_theme').val() + '/content.css',
		disk_cache : true,
		debug : false,
		external_link_list_url: websiteUrl + 'backend/backend_page/linkslist/',
		valid_elements: '*[*]'
	})
})

