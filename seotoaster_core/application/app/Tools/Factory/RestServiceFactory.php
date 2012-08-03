<?php

class Tools_Factory_RestServiceFactory {

	const SERVICE_CLASS_PATTERN = 'Api_%s_%s';

	public static function createService($plugin, $name, $options = array()) {
		$serviceClassName = sprintf(self::SERVICE_CLASS_PATTERN, ucfirst(strtolower($plugin)), ucfirst(strtolower($name)));
        if(!Tools_Plugins_Tools::loaderCanExec($plugin)) {
            throw new Exceptions_SeotoasterPluginException('Can not create ' . $serviceClassName . ' plugin. You have to install the proper loader');
        }
		$zendLoader = Zend_Loader_Autoloader::getInstance();
		$zendLoader->suppressNotFoundWarnings(true);
		if ($zendLoader->autoload($serviceClassName)){
			$frontController = Zend_Controller_Front::getInstance();
			return new $serviceClassName($frontController->getRequest(), $frontController->getResponse());
		}
		return false;
	}

}

