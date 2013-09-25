window.onload = function() {
	function addIcon(el, entity) {
		var html = el.innerHTML;
		el.innerHTML = '<span class="toaster-icon">' + entity + '</span>' + html;
	}
	var icons = {
		'icon-edit' : '&#xe000;',
		'icon-folder-upload' : '&#xe002;',
		'icon-angle-down' : '&#xf107;',
		'icon-plus' : '&#xf067;',
		'icon-remove' : '&#xf00d;',
		'icon-save' : '&#xf0c7;',
		'icon-check' : '&#xe003;',
		'icon-ok' : '&#xf00c;',
		'icon-search' : '&#xf002;',
		'icon-chevron-right' : '&#xf054;',
		'icon-chevron-left' : '&#xf053;',
		'icon-plus-sign' : '&#xf055;',
		'icon-refresh' : '&#xf021;',
		'icon-calendar' : '&#xf073;',
		'icon-copy' : '&#xe007;',
		'icon-tags' : '&#xf02c;',
		'icon-minus-sign' : '&#xf056;',
		'icon-remove-sign' : '&#xf057;',
		'icon-ok-sign' : '&#xf058;',
		'icon-question-sign' : '&#xf059;',
		'icon-exclamation-sign' : '&#xf06a;',
		'icon-info-sign' : '&#xf05a;',
		'icon-arrow-right' : '&#xe00a;',
		'icon-arrow-down' : '&#xe00b;',
		'icon-move' : '&#xe00c;',
		'icon-copy-2' : '&#xe00d;',
		'icon-spinner' : '&#xe00e;',
		'icon-help' : '&#xe004;',
		'icon-remove-2' : '&#xe005;',
		'icon-box-add' : '&#xe006;',
		'icon-review' : '&#xe00f;',
		'icon-toaster' : '&#xe010;',
		'icon-cart' : '&#xe011;',
		'icon-remarketing' : '&#xe012;',
		'icon-quote' : '&#xe008;',
		'icon-action-emails' : '&#xe001;',
		'icon-sort' : '&#xf0dc;',
		'icon-sort-down' : '&#xf0dd;',
		'icon-sort-up' : '&#xf0de;',
		'icon-pencil' : '&#xf040;',
		'icon-book' : '&#xf02d;',
		'icon-gplus' : '&#xe009;',
		'icon-facebook' : '&#xe013;',
		'icon-twitter' : '&#xe014;',
		'icon-feed' : '&#xe015;',
		'icon-linkedin' : '&#xe016;',
		'icon-youtube' : '&#xe017;',
		'icon-pinterest' : '&#xe018;'
	};
	var els = $('[class^="icon-"], [class*=" icon-"]')
	/*$('[class^="icon-"], [class*=" icon-"]').each(function() {
		var code = $(this).attr('class').match(/icon-[^\s'"]+/);
		$(this).prepend('<span class="toaster-icon">'+icons[code[0]]+'</span>');
	});*/
	for (i = 0, els; ; i += 1) {
		el = els[i];
		if(!el) {
			break;
		}
		c = el.className;
		c = c.match(/icon-[^\s'"]+/);
		if (c && icons[c[0]]) {
			addIcon(el, icons[c[0]]);
		}
	}
};