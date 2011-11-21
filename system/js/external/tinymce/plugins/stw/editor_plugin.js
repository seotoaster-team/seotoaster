(function() {
	tinymce.create('tinymce.plugins.SeotoasterWidgets', {
		createControl: function(n, cm) {
			switch (n) {
				case 'widgets':
					var widgetList = cm.createListBox('widgets', {
						title: 'Toaster codes',
						onselect: function(v) {
							$('textarea.tinymce').tinymce().execCommand('mceInsertContent', false, v)
						}
					});
					$.ajax({
						type: 'post',
						url: '/backend/backend_content/loadwidgets/',
						success: function(widgets) {
							for(var i in widgets) {
								for(var j in widgets[i]) {
									if(typeof widgets[i][j].alias != 'undefined') {
										widgetList.add(widgets[i][j].alias, '{$' + widgets[i][j].option + '}');
									}
									else {
										widgetList.add('{$' + widgets[i][j] + '}', '{$' + widgets[i][j] + '}');
									}
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
})();


