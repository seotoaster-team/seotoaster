<?php

class Tools_Widgets_Tools {

	public static function getNames() {
		return Tools_Filesystem_Tools::scanDirectoryForDirs(CORE . '/application/app/Widgets/');
	}

	public static function getAllowedOptions() {
		return self::_getData('getAllowedOptions');
	}

	public static function getWidgetmakerContent() {
		return self::_getData('getWidgetMakerContent');
	}

	private static function _getData($method) {
		$widgetsData = array();
		$widgetsNames = self::getNames();
		if(!empty ($widgetsNames)) {
			foreach ($widgetsNames as $widgetName) {
				$widgetName = 'Widgets_' . $widgetName . '_' . $widgetName;
				if(method_exists($widgetName, $method)) {
					$widgetsData[] = $widgetName::$method();
				}
			}
		}
		return $widgetsData;
	}
}

