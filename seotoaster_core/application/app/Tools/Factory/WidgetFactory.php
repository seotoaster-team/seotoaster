<?php

class Tools_Factory_WidgetFactory {

	private function  __construct() {}

	public static function createWidget($name, $options = array(), $toasterOptions = array()) {
		$name = ucfirst(strtolower($name));
		if(self::_validate($name)) {
			$widgetClassName = 'Widgets_' . $name . '_' . $name;
			return new $widgetClassName($options, $toasterOptions);
		}
		throw new Exceptions_SeotoasterException('Cannot create a widget: ' . $name);
	}

	private static function _validate($name) {
		$incPath = explode(PATH_SEPARATOR, get_include_path());
		if(is_array($incPath) && !empty($incPath)) {
			foreach($incPath as $path) {
				$widgetDirectory = $path . '/Widgets/' . $name;
				if(is_dir($widgetDirectory) && file_exists($widgetDirectory . '/' . $name . '.php')) {
					return true;
				}
			}
			return false;
		}
		return false;
	}
}

