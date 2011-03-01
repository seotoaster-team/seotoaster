<?php

class Tools_Plugins_Tools {

	public static function fetchPluginsMenu($userRole = null) {
		$additionalMenu = array();
		$pluginMapper   = new Application_Model_Mappers_PluginMapper();
		$enabledPlugins = $pluginMapper->findEnabled();
		if(is_array($enabledPlugins) && !empty ($enabledPlugins)) {
			$miscData    = Zend_Registry::get('misc');
			$websiteData = Zend_Registry::get('website');
			$pluginDirPath  = $websiteData['path'] . $miscData['pluginsPath'];
			foreach ($enabledPlugins as $plugin) {
				if($plugin instanceof Application_Model_Models_Plugin) {
					$pluginConfigPath = $pluginDirPath . $plugin->getName() . '/config/config.ini';
					if(file_exists($pluginConfigPath)) {
						try {
							$configIni = new Zend_Config_Ini($pluginConfigPath);
							if(isset($configIni->cpanel)) {
								$additionalMenu[$plugin->getName()]['title'] = strtoupper((isset($configIni->cpanel->title)) ? $configIni->cpanel->title : $plugin->getName());
								if(isset($configIni->cpanel->items)) {
									$items = $configIni->cpanel->items->toArray();
									if(isset($configIni->$userRole) && isset($configIni->$userRole->items)) {
										$items = array_merge($items, $configIni->$userRole->items->toArray());
									}
									$additionalMenu[$plugin->getName()]['items'] = array_map(array('self', '_processPluginMenuItem'), $items);
								}
							}
						}
						catch (Zend_Config_Exception $zce) {
							//Zend_Debug::dump($zce->getMessage()); die(); //development
							continue; //production
						}
					}
				}
			}
		}
		return $additionalMenu;
	}

	private static function _processPluginMenuItem($item) {
		$websiteData = Zend_Registry::get('website');
		$replaceMap = array(
			'{url}' => $websiteData['url'],
			'\''    => '"'
		);
		foreach ($replaceMap as $key => $replace) {
			$item = str_replace($key, $replace, $item);
		}
		return $item;
	}

	public static function getWidgetmakerContent() {
		return self::_getData('getWidgetMakerContent');
	}

	private static function _getData($method) {
		$pluginsData = array();
		$pluginMapper   = new Application_Model_Mappers_PluginMapper();
		$enabledPlugins = $pluginMapper->findEnabled();
		if(is_array($enabledPlugins) && !empty ($enabledPlugins)) {
			foreach ($enabledPlugins as $plugin) {
				$pluginClassName = ucfirst($plugin->getName());
				$pluginPath = 'plugins/' . $plugin->getName() . '/' . $pluginClassName . '.php';
				if(file_exists($pluginPath)) {
					require_once $pluginPath;
					if(method_exists($pluginClassName, $method)) {
						$pluginsData[] = $pluginClassName::$method();
					}
				}
			}
		}
		return $pluginsData;
	}

	public function fetchPluginsRoutes() {
		
	}

}

