<?php

class Tools_Widgets_Tools {

	public static function getNames() {
		$includePath = explode(PATH_SEPARATOR, get_include_path());
		$widgetsNames = array();
		foreach ($includePath as $path) {
			if (is_readable($path.DIRECTORY_SEPARATOR.'Widgets')) {
				$widgetsNames = array_merge($widgetsNames, Tools_Filesystem_Tools::scanDirectoryForDirs($path.DIRECTORY_SEPARATOR.'Widgets'));
			}
		}
		sort($widgetsNames);
		return $widgetsNames;
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

