<?php

class Tools_Factory_WidgetFactory {

	private function  __construct() {}

	public static function createWidget($name, $options = array(), $toasterOptions = array()) {
		$name = ucfirst(strtolower($name));
		self::_validate($name);
		$widgetClassName = 'Widgets_' . $name . '_' . $name;
		return new $widgetClassName($options, $toasterOptions);
	}

	private static function _validate($name) {
//		$wigetDirectory = CORE . 'application/app/Widgets/' . $name;
//		if(!is_dir($wigetDirectory)) {
//			throw new Exceptions_SeotoasterException($wigetDirectory . ' is not a directory.');
//		}
//		$widgetClassPath = $wigetDirectory . '/' . $name . '.php';
//		if(!file_exists($widgetClassPath)) {
//			throw new Exceptions_SeotoasterException($widgetClassPath . ' not found.');
//		}
		if(!preg_match('~^[A-Za-z0-9]+$~ui',$name)) {
			throw new Exceptions_SeotoasterException('Wrong widget name: ' . $name);
		}
	}
}

