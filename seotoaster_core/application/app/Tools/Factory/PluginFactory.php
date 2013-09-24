<?php

class Tools_Factory_PluginFactory {

	private function  __construct() {}

	public static function createPlugin($name, $options = array(), $toasterOptions = array()) {
		$pluginClassName = ucfirst($name);
        if(!Tools_Plugins_Tools::loaderCanExec($name)) {
            throw new Exceptions_SeotoasterPluginException('Sorry, ' . $pluginClassName . ' plug-in requires Ioncube. Please install the proper Ioncube loader on your web server at <a href="http://www.ioncube.com/loaders.php" target="_blank">www.ioncube.com/loaders.php</a> or request your web host to do it for you.');
        }
		self::_validate($pluginClassName);
		return new $pluginClassName($options, $toasterOptions);
	}

	private static function _validate($name) {
		$miscData        = Zend_Registry::get('misc');
		$websiteHelper   = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$pluginDirectory = $websiteHelper->getPath() . $miscData['pluginsPath'] . strtolower($name);
		unset($miscData);
		unset($websiteHelper);
		if(!is_dir($pluginDirectory)) {
			if(Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_ADMINPANEL)) {
				throw new Exceptions_SeotoasterPluginException($pluginDirectory . ' is not a directory.');
			}
			throw new Exceptions_SeotoasterPluginException('<!-- ' . $pluginDirectory . ' is not a directory. -->');
		}
		$pluginClassPath = $pluginDirectory . '/' . $name . '.php';
		if(!file_exists($pluginClassPath)) {
			if(Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_ADMINPANEL)) {
				throw new Exceptions_SeotoasterPluginException($pluginClassPath . ' not found.');
			}
			throw new Exceptions_SeotoasterPluginException('<!--' . $pluginClassPath . ' not found. -->');
		}
		require_once $pluginClassPath;
	}

    public static function validate($name) {
        self::_validate($name);
    }

}

