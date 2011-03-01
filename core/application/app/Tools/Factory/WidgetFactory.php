<?php

class Tools_Factory_WidgetFactory {
	
	private function  __construct() {}

	public static function factory($name, $options = array(), $toasterOptions = array()) {
		$name = ucfirst($name);
		self::_validate($name);
		$widgetClassName = 'Widgets_' . $name . '_' . $name;
		return new $widgetClassName($options, $toasterOptions);
	}

	private static function _validate($name) {
		$wigetDirectory = CORE . '/application/app/Widgets/' . $name;
		if(!is_dir($wigetDirectory)) {
			throw new Exceptions_SeotoasterException($wigetDirectory . ' is not a directory.');
		}
		$widgetClassPath = $wigetDirectory . '/' . $name . '.php';
		if(!file_exists($widgetClassPath)) {
			throw new Exceptions_SeotoasterException($widgetClassPath . ' not found.');
		}
	}
}

