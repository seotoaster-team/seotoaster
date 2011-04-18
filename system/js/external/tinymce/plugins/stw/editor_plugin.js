(function() {
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
})();
	

	