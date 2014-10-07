<?php

class Tools_Plugins_Tools {

	const CONFIGINI_PATH   = 'config/config.ini';

    const LOADER_EXTENSION = 'IonCube Loader';

    const PLUGIN_TRANSLATIONS_CACHE_ID = 'plugin_translation';

    /**
     * @var string Path to plugins
     */
    private static $_pluginsPath;

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
                $values    = array();

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
						'items' => array(),
                        'values' => array()
					);
				}

				if(isset($configIni->cpanel->items)) {
					$items = array_values($configIni->cpanel->items->toArray());
				}
                if(isset($configIni->cpanel->values)) {
                    $values = array_values($configIni->cpanel->values->toArray());
                }
				if(isset($configIni->$userRole) && isset($configIni->$userRole->items)) {
					$items = array_merge($items, array_values($configIni->$userRole->items->toArray()));
				}
                if(isset($configIni->$userRole) && isset($configIni->$userRole->values)) {
                    $values = array_merge($values, array_values($configIni->$userRole->values->toArray()));
                }

				$websiteUrl = Zend_Controller_Action_HelperBroker::getStaticHelper('website')->getUrl();
                foreach ($values as $value) {
                    array_push($additionalMenu[$title]['values'], $value);
                    unset($value);
                }

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
                error_log("(plugin: " . strtolower(get_called_class()) . ") " . $zce->getMessage() . "\n" . $zce->getTraceAsString());
            }
            if(!isset($configIni->actiontriggers)) {
                continue;
            }
            $triggers = array_merge($triggers, $configIni->actiontriggers->toArray());
        }
        return $triggers;
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


	public static function getEnabledPlugins($namesOnly = false) {
		$cacheHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Cache');
		$cacheHelper->init();
        $cacheKeyPostfix = ($namesOnly) ? 'Names' : '';
		if(null === ($enabledPlugins = $cacheHelper->load('enabledPlugins' . $cacheKeyPostfix, 'plugins_'))) {
			$enabledPlugins = Application_Model_Mappers_PluginMapper::getInstance()->findEnabled();

            // if we do not have the proper encoder loaded we have to exclude plugins that, requires that encoder, from enabled
            if (!extension_loaded(self::LOADER_EXTENSION)) {
                $miscData         = Zend_Registry::get('misc');
                $websiteData      = Zend_Registry::get('website');
                $pluginsPath = $websiteData['path'] . $miscData['pluginsPath'];
                unset($miscData, $websiteData);
                $enabledPlugins = array_filter(
                    $enabledPlugins,
                    function ($plugin) use ($pluginsPath) {
                        return !file_exists($pluginsPath . $plugin->getName() . '/.toasted');
                    }
                );
            }

            if($namesOnly) {
                $enabledPlugins = array_map(function($plugin) { return $plugin->getName(); }, $enabledPlugins);
            }

			$cacheHelper->save('enabledPlugins' . $cacheKeyPostfix, $enabledPlugins, 'plugins_', array('plugins'), Helpers_Action_Cache::CACHE_LONG);
		}
		return $enabledPlugins;
	}

    /**
     * Checks if plugins can be executed.
     * To prevent fatal error on calling encoded plugins when proper loader extension is not installed
     * @param $name Plugin name
     * @return bool
     */
    public static function loaderCanExec($name)
    {
        if (!extension_loaded(self::LOADER_EXTENSION)) {
            return !file_exists(self::getPluginsPath() . $name . DIRECTORY_SEPARATOR . '.toasted');
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
        $routesFile = APPLICATION_PATH . '/configs/' . SITE_NAME . '.routes.xml';
        if (!is_file($routesFile)) {
            $routesFile = APPLICATION_PATH . '/configs/routes.xml';
        }
        $routes = array();
        if (file_exists($routesFile)) {
            $routes = new Zend_Config_Xml($routesFile, 'routes');
            $routes = $routes->toArray();
        }
        $miscData = Zend_Registry::get('misc');
        $websiteData = Zend_Registry::get('website');
        return array(
            'routesPath'     => $routesFile,
            'routes'         => $routes,
            'pluginsDirPath' => $websiteData['path'] . $miscData['pluginsPath']
        );
	}

    /**
     * Register routes in routes.xml for given plugin(s)
     * @param $plugins array|Application_Model_Models_Plugin|string Accept plugin name, plugin model or array with plugin models
     */
    public static function registerPluginRoute($plugins)
    {
        if (!is_array($plugins)) {
            $plugins = array($plugins);
        }

        $routesData = self::_initValues();
        $routesUpdated = false;

        foreach ($plugins as $plugin) {
            if ($plugin instanceof Application_Model_Models_Plugin) {
                $pluginName = $plugin->getName();
            } elseif (is_string($plugin)) {
                $pluginName = strtolower($plugin);
            } else {
                continue;
            }

            $pluginConfigPath = $routesData['pluginsDirPath'] . $pluginName . '/config/config.ini';
            if (!file_exists($pluginConfigPath)) {
                continue;
            }

            try {
                $route = new Zend_Config_Ini($pluginConfigPath, 'route');

                if ($route !== null && !array_key_exists($pluginName, $routesData['routes'])) {
                    $pluginRoute = self::_formatPluginRoute($route->toArray(), $pluginName);

                    $routesData['routes'][$pluginRoute['name']] = $pluginRoute['data'];

                    $routesUpdated = $routesUpdated || true;
                }
            } catch (Exception $e) {
                error_log($e->getMessage());
            }
        }

        if ($routesUpdated) {
            $writer = new Zend_Config_Writer_Xml();
            $writer->setConfig(new Zend_Config(array('routes' => $routesData['routes'])));
            $writer->write($routesData['routesPath']);
        }
    }

    /**
     * Remove registered routes for given plugin(s)
     * @param $plugins array|Application_Model_Models_Plugin|string Accept plugin name, plugin model or array with plugin models
     */
    public static function removePluginRoute($plugins) {
        if (!is_array($plugins)) {
            $plugins = array($plugins);
        }

        $routesData = self::_initValues();
        $routesUpdated = false;

        foreach ($plugins as $plugin) {
            if ($plugin instanceof Application_Model_Models_Plugin) {
                $pluginName = $plugin->getName();
            } elseif (is_string($plugin)) {
                $pluginName = strtolower($plugin);
            } else {
                continue;
            }

            $pluginConfigPath = $routesData['pluginsDirPath'] . $pluginName . '/config/config.ini';
            if (!file_exists($pluginConfigPath)) {
                continue;
            }

            try {
                $route = new Zend_Config_Ini($pluginConfigPath, 'route');
                if ($route === null) {
                    continue;
                }
                $pluginRoute = self::_formatPluginRoute($route->toArray(), $pluginName);
                if (!empty($routesData['routes']) && array_key_exists($pluginRoute['name'], $routesData['routes'])) {
                    unset($routesData['routes'][$pluginRoute['name']]);
                    $routesUpdated = $routesUpdated || true;
                }
            } catch (Exception $e) {
                error_log($e->getMessage());
            }
        }

        if ($routesUpdated) {
            $writer = new Zend_Config_Writer_Xml();
            $writer->setConfig(new Zend_Config(array('routes' => $routesData['routes'])));
            $writer->write($routesData['routesPath']);
        }
	}

    /**
     * Remove all registered routes for all enabled plugins
     */
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

    /**
     * @deprecated
     */
    public static function findAvialablePlugins()
    {
        return self::findAvailablePlugins();
    }

    /**
     * Return list of available plugins
     * @return array List of plugins
     */
    public static function findAvailablePlugins()
    {
        $website = Zend_Controller_Action_HelperBroker::getStaticHelper('Website');
        $misc = Zend_Registry::get('misc');
        $pluginsPath = $website->getPath() . $misc['pluginsPath'];
        $pluginsDirs = Tools_Filesystem_Tools::scanDirectoryForDirs($pluginsPath);
        $plugins = array();
        if (!empty ($pluginsDirs)) {
            foreach ($pluginsDirs as $pluginDir) {
                $fullPluginPath = $pluginsPath . DIRECTORY_SEPARATOR . $pluginDir . DIRECTORY_SEPARATOR;

                // check if plugin is bundle, then do not show in the plugin management screen
                if (is_file($fullPluginPath . '.bundle')) {
                    continue;
                }

                if (is_readable($fullPluginPath . 'readme.txt')
                    && is_readable($fullPluginPath . ucfirst($pluginDir) . '.php')
                ) {
                    $plugins[] = $pluginDir;
                }

            }
        }
        return $plugins;
    }

    /**
     * Find preview file url for plugin
     * @param $pluginName Plugin name
     * @return bool|string Plugin preview URL. False if nothing found
     */
    public static function findPluginPreview($pluginName) {
		$website     = Zend_Controller_Action_HelperBroker::getStaticHelper('Website');
		$misc        = Zend_Registry::get('misc');
        $files = glob(
            $website->getPath() . $misc['pluginsPath'] . $pluginName . DIRECTORY_SEPARATOR . 'preview.{png,jpg,jpeg,gif}',
            GLOB_BRACE
        );

        if (!empty($files)) {
            $preview = str_replace($website->getPath(), $website->getUrl(), reset($files));
            return  Tools_Filesystem_Tools::cleanWinPath($preview);
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
                            error_log($zce->getMessage());
                            continue; //production
                        }
                    }
                }
            }
        }

        return $path;
    }

    /**
     * Register include path for all enabled plugins
     * @param bool $force Force reload include path if true given
     */
    public static function registerPluginsIncludePath($force = false)
    {
        $cacheHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('cache')->init();

        if ($force) {
            $cacheHelper->clean(null, null, array('plugins'));
        }

        if (null === ($pluginIncludePath = $cacheHelper->load('includePath', 'plugins_')) || $force) {
            $pluginIncludePath = Tools_Plugins_Tools::fetchPluginsIncludePath();
            $cacheHelper->save(
                'includePath',
                $pluginIncludePath,
                'plugins_',
                array('plugins'),
                Helpers_Action_Cache::CACHE_LONG
            );
        }
        if (!empty($pluginIncludePath)) {
            set_include_path(implode(PATH_SEPARATOR, $pluginIncludePath) . PATH_SEPARATOR . get_include_path());
        }
    }

    /**
     * Register plugins translation files into toasters translator
     * Method find for all enabled plugins language files for current language. Combine them in one array and cache
     */
    public static function registerPluginsTranslations()
    {
        $cacheHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('cache')->init();

        $translate = Zend_Registry::get('Zend_Translate'); /** @var $translate Zend_Translate_Adapter_Array */

        $locale = $translate->getLocale();

        $cacheId = self::PLUGIN_TRANSLATIONS_CACHE_ID . '_' . $locale;
        $pluginsTranslations = $cacheHelper->load($cacheId, 'plugins_');

        if (null === $pluginsTranslations) {
            $plugins = self::getEnabledPlugins();

            $pluginsPath = self::getPluginsPath();
            $pluginsTranslations = array();
            foreach ($plugins as $plugin) {
                $lngFile = $pluginsPath.$plugin->getName().DIRECTORY_SEPARATOR.'system/languages/'.$locale.'.lng';
                if (is_readable($lngFile)) {
                    $langArray = require($lngFile);
                    if (!empty($langArray) && is_array($langArray)) {
                        $pluginsTranslations = array_merge($pluginsTranslations, $langArray);
                    }
                }
            }
            $pluginsTranslations = array_filter($pluginsTranslations);
            $cacheHelper->save(
                $cacheId,
                $pluginsTranslations,
                'plugins_',
                array('plugins', self::PLUGIN_TRANSLATIONS_CACHE_ID),
                Helpers_Action_Cache::CACHE_LONG * 10
            );
        }

        if (!empty($pluginsTranslations)) {
            $translate->addTranslation(
                array(
                    'content' => $pluginsTranslations,
                    'locale'  => $locale,
                    'reload'  => true
                )
            );
        }
    }

    /**
     * Returns path to plugins folder
     * @return string Path to plugins folder
     */
    public static function getPluginsPath()
    {
        if (empty(self::$_pluginsPath)) {
            $websiteData = Zend_Registry::get('website');
            $miscData = Zend_Registry::get('misc');
            self::$_pluginsPath = $websiteData['path'] . $miscData['pluginsPath'];
        }

        return self::$_pluginsPath;
    }
}
