<?php

class Tools_Plugins_Tools {

	const CONFIGINI_PATH   = 'config/config.ini';

    const LOADER_EXTENSION = 'IonCube Loader';

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
				$configIni = new Zend_Config_Ini($pluginConfigPath);
				$items     = array();

				if(!isset($configIni->cpanel)) {
					continue;
				}

				$title = strtoupper((isset($configIni->cpanel->title)) ? $configIni->cpanel->title : '');
				if(!$title) {
                    if(isset($configIni->$userRole) && isset($configIni->$userRole->title)) {
                        $title = strtoupper($configIni->$userRole->title);
                    }
                }

                if (!isset($additionalMenu[$title])){
					$additionalMenu[$title] = array(
						'title' => $title,
						'items' => array()
					);
				}

				if(isset($configIni->cpanel->items)) {
					$items = array_values($configIni->cpanel->items->toArray());
				}
				if(isset($configIni->$userRole) && isset($configIni->$userRole->items)) {
					$items = array_merge($items, array_values($configIni->$userRole->items->toArray()));
				}

				$websiteUrl = Zend_Controller_Action_HelperBroker::getStaticHelper('website')->getUrl();
				foreach ($items as $item) {
					if (is_string($item)){
						$item = strtr($item, array(
							'{url}' => $websiteUrl,
							'\''    => '"'
						));
					} elseif (is_array($item)) {
						$item = array_merge(
							array(
								'run'       => 'index',
								'name'      => $plugin->getName(),
								'width'     => null,
								'height'    => null
							),
							$item
						);
						if (isset($item['section'])){
							$subTitle = strtoupper($item['section']);
							if (!isset($additionalMenu[$subTitle])){
								$additionalMenu[$subTitle] = array(
									'title' => $subTitle,
									'items' => array()
								);
							}
							array_push($additionalMenu[$subTitle]['items'], $item);
							unset($subTitle);
							continue;
						}
					}
					array_push($additionalMenu[$title]['items'], $item);
					unset($item);
				}
			}
			catch (Zend_Config_Exception $zce) {
				//Zend_Debug::dump($zce->getMessage()); die(); //development
				continue; //production
			}
		}

        sort($additionalMenu);
		return $additionalMenu;
	}


    /**
     * Fetch plugins action e-mails triggers from config file
     *
     * @static
     * @return mixed
     */
    public static function fetchPluginsTriggers() {
        $triggers      = array();
        $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $miscConfig    = Zend_Registry::get('misc');

        $enabledPlugins = self::getEnabledPlugins();
        if(!is_array($enabledPlugins) || empty($enabledPlugins)) {
            return false;
        }

        $pluginDirPath  = $websiteHelper->getPath() . $miscConfig['pluginsPath'];

        foreach($enabledPlugins as $plugin) {
            $configIniPath = $pluginDirPath . $plugin->getName() . '/' . self::CONFIGINI_PATH;

            if(!file_exists($configIniPath)) {
                continue;
            }

            try {
                $configIni = new Zend_Config_Ini($configIniPath);
            } catch (Zend_Config_Exception $zce) {
                if(APPLICATION_ENV == 'development') {
                    Zend_Debug::dump($zce->getMessage() . '<br />' . $zce->getTraceAsString());
                }
                error_log("(plugin: " . strtolower(get_called_class()) . ") " . $se->getMessage() . "\n" . $se->getTraceAsString());
            }
            if(!isset($configIni->actiontriggers)) {
                continue;
            }
            $triggers = array_merge($triggers, $configIni->actiontriggers->toArray());
        }
        return $triggers;
    }


	/**
	 * @deprecated
	 */
	private static function _processPluginMenuItem(&$item, $index, $plugin) {
		$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		if (is_string($item)){
			$item = strtr($item, array(
				'{url}' => $websiteHelper->getUrl(), '\''    => '"'
			));
		} elseif (is_array($item)) {
			$item = array_merge(
				array(
					'run'       => 'index',
					'name'      => $plugin->getName(),
					'width'     => null,
					'height'    => null
				),
				$item
			);
		}
		return $item;
	}

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


    /**
     * @todo Should be moved to the e-commerce plugin
     *
     * @static
     * @return array
     */
    public static function getEcommerceConfigTabs() {
        return self::_getData('getEcommerceConfigTab', array('ecommerce'));
    }

	private static function _getData($method, $tags = array()) {
		$pluginsData = array();
		$enabledPlugins = (!empty($tags)) ? self::getPluginsByTags($tags) : self::getEnabledPlugins();
		if(is_array($enabledPlugins) && !empty ($enabledPlugins)) {
			foreach ($enabledPlugins as $plugin) {
                $pluginClassName = ucfirst($plugin->getName());
                $reflection      = new Zend_Reflection_Class($pluginClassName);
                if($reflection->hasMethod($method)) {
                    $pluginsData[] = $pluginClassName::$method();
                }
			}
		}
		return $pluginsData;
	}

    public static function runStatic($method, $plugin = null) {
        $enabledPlugins = self::getEnabledPlugins(true);
        if(!$plugin) {
            foreach($enabledPlugins as $enabledPlugin) {
                $result = self::_runStatic($enabledPlugin, $method);
                if($result) {
                    return $result;
                }
            }
	        return false;
        } else {
            if(in_array($plugin, $enabledPlugins)) {
                return self::_runStatic($plugin, $method);
            }
            return false;
        }

    }

    private static function _runStatic($pluginClass, $method) {
        $reflection = new Zend_Reflection_Class(ucfirst($pluginClass));
        if($reflection->hasMethod($method)) {
            return $pluginClass::$method();
        }
        return false;
    }

	public static function fetchPluginsRoutes() {
		$routes         = array();
		$enabledPlugins = self::getEnabledPlugins();
		if(is_array($enabledPlugins) && !empty ($enabledPlugins)) {
			$routesPath  = is_file(APPLICATION_PATH . '/configs/'.SITE_NAME.'.routes.xml') ? APPLICATION_PATH . '/configs/'.SITE_NAME.'.routes.xml' : APPLICATION_PATH . '/configs/routes.xml' ;
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

	public static function getEnabledPlugins($namesOnly = false) {
		$cacheHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Cache');
		$cacheHelper->init();
        $cacheKeyPostfix = ($namesOnly) ? 'Names' : '';
		if(null === ($enabledPlugins = $cacheHelper->load('enabledPlugins' . $cacheKeyPostfix, 'plugins_'))) {
			$enabledPlugins = Application_Model_Mappers_PluginMapper::getInstance()->findEnabled();

            // if we do not have the proper encoder loaded we have to exclude plugins that, requires that encoder, from enabled
            if(!extension_loaded(self::LOADER_EXTENSION)) {
                $pluginsData = self::_initValues();
                foreach($enabledPlugins as $key => $plugin) {
                    if(file_exists($pluginsData['pluginsDirPath'] . $plugin->getName() . '/.toasted')) {
                        unset($enabledPlugins[$key]);
                    }
                }
            }

            if($namesOnly) {
                $enabledPlugins = array_map(function($plugin) { return $plugin->getName(); }, $enabledPlugins);
            }

			$cacheHelper->save('enabledPlugins' . $cacheKeyPostfix, $enabledPlugins, 'plugins_', array('plugins'), Helpers_Action_Cache::CACHE_LONG);
		}
		return $enabledPlugins;
	}

    public static function loaderCanExec($name) {
        if(!extension_loaded(self::LOADER_EXTENSION)) {
            $pluginsData = self::_initValues();
            if(file_exists($pluginsData['pluginsDirPath'] . $name . '/.toasted')) {
                return false;
            }
            return true;
        }
        return true;
    }

    /**
     * Find plugins with specified tags
     *
     * @static
     * @param array $tags
     * @return mixed
     */
    public static function getPluginsByTags($tags) {
        if(!is_array($tags)) {
            $tags = (array)$tags;
        }
        if(empty($tags)) {
            return null;
        }
        $plugins = self::getEnabledPlugins();
        if(!is_array($plugins) || empty($plugins)) {
            return null;
        }
        return array_filter($plugins, function($plugin) use($tags) {
            $pluginTags = $plugin->getTags();
            if(is_array($pluginTags) && array_intersect($tags, $pluginTags)) {
                return $plugin;
            }
        });
    }

	private static function _initValues() {
		$routesPath  = APPLICATION_PATH . '/configs/' . SITE_NAME . 'routes.xml';
		$routes      = array();
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
		$routeName  = $routeData['name'];
		$method     = $routeData['method'];
		unset ($routeData['name']);
		unset ($routeData['method']);
		$route = array(
			'name' => $routeName,
			'data' => $routeData
		);
		if (!isset($routeData['defaults']) || empty($routeData['defaults'])){
			$routeData['defaults'] = array();
		}
		$route['data']['defaults'] = array_merge($routeData['defaults'], array(
			'controller' => 'backend_plugin',
			'action'     => 'fireaction',
			'name'       => $pluginName,
			'run'        => $method
		));
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

