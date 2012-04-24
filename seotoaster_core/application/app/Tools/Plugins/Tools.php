<?php

class Tools_Plugins_Tools {

	const CONFIGINI_PATH = 'config/config.ini';

	public static function fetchPluginsMenu($userRole = null) {
		$additionalMenu = array();
		$enabledPlugins = self::getEnabledPlugins();

		if(!is_array($enabledPlugins) || empty ($enabledPlugins)) {
			return;
		}

		$miscData       = Zend_Registry::get('misc');
		$websiteData    = Zend_Registry::get('website');
		$pluginDirPath  = $websiteData['path'] . $miscData['pluginsPath'];

		foreach ($enabledPlugins as $plugin) {

			if(!$plugin instanceof Application_Model_Models_Plugin) {
				throw new Exceptions_SeotoasterPluginException('Cannot fetch plugin menu. Given parameter should Application_Model_Models_Plugin instance.');
			}

			$pluginConfigPath = $pluginDirPath . $plugin->getName() . '/' . self::CONFIGINI_PATH;

			if(!file_exists($pluginConfigPath)) {
				continue;
			}

			try {
				$configIni  = new Zend_Config_Ini($pluginConfigPath);
				$configData = (isset($configIni->$userRole) ? $configIni->$userRole->toArray() : (isset($configIni->cpanel) ? $configIni->cpanel->toArray() : array()));
				if(empty($configData)) {
					continue;
				}
				$additionalMenu[$plugin->getName()]['title'] = strtoupper((isset($configData['title'])) ? $configData['title'] : $plugin->getName());
				if(isset($configData['items'])) {
					$additionalMenu[$plugin->getName()]['items'] = array_map(function($item) {
						$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
						$replaceMap    = array('search'  => array('{url}', '\''), 'replace' => array($websiteHelper->getUrl(), '"'));
						return str_replace($replaceMap['search'], $replaceMap['replace'], $item);
					}, $configData['items']);
				}
			}
			catch (Zend_Config_Exception $zce) {
				//error_log($zce->getMessage() . "\n" . $zce->getTraceAsString()); die(); //development
				continue; //production
			}
		}

		return $additionalMenu;
	}

//	private static function _processPluginMenuItem($item) {
//		$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
//		$replaceMap    = array(
//			'search'  => array(
//				'{url}',
//				'\''
//			),
//			'replace' => array(
//				$websiteHelper->getUrl(),
//				'"'
//			)
//		);
//		return str_replace($replaceMap['search'], $replaceMap['replace'], $item);
//	}

	public static function getWidgetmakerContent() {
		return self::_getData('getWidgetMakerContent');
	}

	public static function getPluginTabContent() {
		return self::_getData('getTabContent');
	}

	public static function getPluginEditorLink() {
		return self::_getData('getEditorLink');
	}

	public static function getPluginEditorTop() {
		return self::_getData('getEditorTop');
	}

	private static function _getData($method) {
		$pluginsData = array();
		$enabledPlugins = self::getEnabledPlugins();
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

	public static function fetchPluginsRoutes() {
		$routes         = array();
		$enabledPlugins = self::getEnabledPlugins();
		if(is_array($enabledPlugins) && !empty ($enabledPlugins)) {
			$routesPath  = APPLICATION_PATH . '/configs/routes.xml';
			if(file_exists($routesPath)) {
				$routes = new Zend_Config_Xml($routesPath);
				$routes = $routes->toArray();
			}
			$miscData       = Zend_Registry::get('misc');
			$websiteData    = Zend_Registry::get('website');
			$pluginDirPath  = $websiteData['path'] . $miscData['pluginsPath'];
			foreach ($enabledPlugins as $plugin) {
				if($plugin instanceof Application_Model_Models_Plugin) {
					$pluginConfigPath = $pluginDirPath . $plugin->getName() . '/config/config.ini';
					if(file_exists($pluginConfigPath)) {
						try {
							$configIni = new Zend_Config_Ini($pluginConfigPath);
							if(!isset($configIni->route)) {
								continue;
							}
							$pluginRoute = self::_formatPluginRoute($configIni->route->toArray(), $plugin->getName());
							if(!empty($routes)) {
								if(!array_key_exists($pluginRoute['name'], $routes['routes'])) {
									$routes['routes'][$pluginRoute['name']] = $pluginRoute['data'];
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
			$writer = new Zend_Config_Writer_Xml();
			$writer->setConfig(new Zend_Config($routes));
			try {
				$writer->write($routesPath);
			}
			catch (Zend_Config_Exception $zce) {
				//Zend_Debug::dump($zce->getMessage());
			}
		}
	}

	public static function getEnabledPlugins() {
		$cacheHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Cache');
		$cacheHelper->init();
		if(null === ($enabledPlugins = $cacheHelper->load('enabledPlugins', 'plugins_'))) {
			$enabledPlugins = Application_Model_Mappers_PluginMapper::getInstance()->findEnabled();
			$cacheHelper->save('enabledPlugins', $enabledPlugins, 'plugins_', array('plugins'), Helpers_Action_Cache::CACHE_LONG);
		}
		return $enabledPlugins;
	}

	private static function _initValues() {
		$routesPath  = APPLICATION_PATH . '/configs/' . SITE_NAME . 'routes.xml';
		if(file_exists($routesPath)) {
			$routes = new Zend_Config_Xml($routesPath);
			$routes = $routes->toArray();
		}
		$miscData         = Zend_Registry::get('misc');
		$websiteData      = Zend_Registry::get('website');
		return array(
			'routesPath'     => $routesPath,
			'routes'         => $routes,
			'pluginsDirPath' => $websiteData['path'] . $miscData['pluginsPath']
		);
	}

	public static function removePluginRoute($pluginName) {
		$routesData = self::_initValues();
		$pluginConfigPath = $routesData['pluginsDirPath'] . $pluginName . '/config/config.ini';
		$routes           = $routesData['routes'];
		if(!file_exists($pluginConfigPath)) {
			return;
		}
		try {
			$configIni = new Zend_Config_Ini($pluginConfigPath);
			if(!isset($configIni->route)) {
				return;
			}
			$pluginRoute = self::_formatPluginRoute($configIni->route->toArray(), $pluginName);
			if(!empty($routes)) {
				if(array_key_exists($pluginRoute['name'], $routes['routes'])) {
					unset($routes['routes'][$pluginRoute['name']]);
					$writer = new Zend_Config_Writer_Xml();
					$writer->setConfig(new Zend_Config($routes));
					$writer->write($routesData['routesPath']);
				}
			}
		}
		catch (Zend_Config_Exception $zce) {
			return;
		}
	}

	public static function removePluginsRoutes() {
		$enabledPlugins = self::getEnabledPlugins();
		foreach ($enabledPlugins as $plugin) {
			self::removePluginRoute($plugin->getName());
		}
	}


	private static function _formatPluginRoute($routeData, $pluginName) {
		$name   = $routeData['name'];
		$method = $routeData['method'];
		unset ($routeData['name']);
		unset ($routeData['method']);
		$route = array(
			'name' => $name,
			'data' => $routeData
		);
		$route['data']['defaults'] = array(
			'controller' => 'backend_plugin',
			'action'     => 'fireaction',
			'name'       => $pluginName,
			'run'        => $method
		);
		return $route;
	}

	public static function findAvialablePlugins() {
		$website     = Zend_Controller_Action_HelperBroker::getStaticHelper('Website');
		$misc        = Zend_Registry::get('misc');
		$pluginsPath = $website->getPath() . $misc['pluginsPath'];
		$pluginsDirs = Tools_Filesystem_Tools::scanDirectoryForDirs($pluginsPath);
		$plugins     = array();
		if(!empty ($pluginsDirs)) {
			foreach ($pluginsDirs as $pluginDir) {
				$required = array(
					'readme.txt',
					ucfirst($pluginDir) . '.php'
				);
				$pluginDirContent = Tools_Filesystem_Tools::scanDirectory($pluginsPath . '/' . $pluginDir, false, false);

				// check if plugin is bundle, then do not show in the plugin managment screen
				if(in_array('.bundle', $pluginDirContent)) {
					continue;
				}

				if($required == (array_intersect($required, $pluginDirContent))) {
					$plugins[] = $pluginDir;
				}
			}
		}
		return $plugins;
	}

	public static function findPluginPreview($pluginName) {
		$website     = Zend_Controller_Action_HelperBroker::getStaticHelper('Website');
		$misc        = Zend_Registry::get('misc');
		$pluginsPath = $website->getPath() . $misc['pluginsPath'] . $pluginName;
		$files       = Tools_Filesystem_Tools::scanDirectory($pluginsPath, false, false);
		array_walk($files, function($file) {
			if(preg_match('~^preview\.(jpg|gif|png)$~ui', $file)) {
				Zend_Registry::set('previewFile', $file);
			}
		});
		if(Zend_Registry::isRegistered('previewFile')) {
			return $website->getUrl() . $misc['pluginsPath'] . $pluginName . '/' .Zend_Registry::get('previewFile');
		}
		return false;
	}

	public static function findPluginByName($pluginName) {
		$plugin       = Application_Model_Mappers_PluginMapper::getInstance()->findByName($pluginName);
		if($plugin instanceof Application_Model_Models_Plugin) {
			$plugin->setPreview(self::findPluginPreview($plugin->getName()));
			return $plugin;
		}
		$plugin = new Application_Model_Models_Plugin();
		$plugin->setName($pluginName);
		try {
			$plugin->setPreview(self::findPluginPreview($plugin->getName()));
		} catch (Exceptions_SeotoasterException $se){
			$plugin->setPreview(Zend_Controller_Action_HelperBroker::getStaticHelper('Website')->getUrl().'system/images/noimage.png');
		}
		$plugin->setStatus(Application_Model_Models_Plugin::DISABLED);
		return $plugin;
	}

    public static function fetchPluginsIncludePath() {
        $path         = array();
        $enabledPlugins = self::getEnabledPlugins();
        if(is_array($enabledPlugins) && !empty ($enabledPlugins)) {
            $miscData       = Zend_Registry::get('misc');
            $websiteData    = Zend_Registry::get('website');
            $pluginDirPath  = $websiteData['path'] . $miscData['pluginsPath'];
            foreach ($enabledPlugins as $plugin) {
                if($plugin instanceof Application_Model_Models_Plugin) {
                    $pluginConfigPath = $pluginDirPath . $plugin->getName() . '/config/config.ini';
                    if(file_exists($pluginConfigPath)) {
                        try {
                            $configIni = new Zend_Config_Ini($pluginConfigPath);
                            if(!isset($configIni->include_path)) {
                                continue;
                            }
                            $includePath = realpath($pluginDirPath . $plugin->getName() .DIRECTORY_SEPARATOR. trim(str_replace(array('\\','/'), DIRECTORY_SEPARATOR, $configIni->include_path)));
                            if (is_dir($includePath) && !in_array($includePath, $path)){
                                array_push($path, $includePath);
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

        return $path;
    }

	public static function registerPluginsIncludePath($force = false){
		$cacheHelper   = Zend_Controller_Action_HelperBroker::getStaticHelper('cache');

		if ($force) {
			$cacheHelper->clean(null, null, array('plugins'));
		}

		if (null === ($pluginIncludePath = $cacheHelper->load('includePath', 'plugins_')) || $force){
			$pluginIncludePath = Tools_Plugins_Tools::fetchPluginsIncludePath();
			$cacheHelper->save('includePath', $pluginIncludePath, 'plugins_', array('plugins'), Helpers_Action_Cache::CACHE_NORMAL);
		}
		if (!empty($pluginIncludePath)){
            set_include_path(implode(PATH_SEPARATOR,$pluginIncludePath).PATH_SEPARATOR.get_include_path());
		}
	}
}

