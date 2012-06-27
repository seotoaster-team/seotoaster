<?php

/**
 * Plugin
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Plugins_Plugin extends Zend_Controller_Plugin_Abstract {

	const PREDISPATCH_METHOD  = 'beforeController';
	const POSTDISPATCH_METHOD = 'afterController';

	public function routeStartup(Zend_Controller_Request_Abstract $request) {
		$sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
		$cacheHelper   = Zend_Controller_Action_HelperBroker::getStaticHelper('cache');
		$sessionHelper->init();
		$cacheHelper->init();
		if(!isset($sessionHelper->pluginRoutesFetched) || $sessionHelper->pluginRoutesFetched !== true) {
			Tools_Plugins_Tools::fetchPluginsRoutes();
			$sessionHelper->pluginRoutesFetched = true;
		}
		Tools_Plugins_Tools::registerPluginsIncludePath();
    }

    public function preDispatch(Zend_Controller_Request_Abstract $request) {
		$this->_triggerToasterPlugins(self::PREDISPATCH_METHOD);
	}

	public function postDispatch(Zend_Controller_Request_Abstract $request) {
		$this->_triggerToasterPlugins(self::POSTDISPATCH_METHOD);
		//replace http with https for internal links if requested via https
		if ($request->isSecure()){
			$websiteConfig = Zend_Registry::get('website');
			$body = strtr($this->_response->getBody(),
				array(
					Zend_Controller_Request_Http::SCHEME_HTTP.'://'.$websiteConfig['url'] => Zend_Controller_Request_Http::SCHEME_HTTPS.'://'.$websiteConfig['url']
				)
			) ;
			$this->_response->setBody($body);
		}
	}

	private function _triggerToasterPlugins($method) {
		$enabledPlugins = Tools_Plugins_Tools::getEnabledPlugins();
		if(is_array($enabledPlugins) && !empty ($enabledPlugins)) {
			//$miscData      = Zend_Registry::get('misc');
			$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Website');

			array_walk($enabledPlugins, function($plugin, $key, $data) {
				try {
					$pluginInstance = Tools_Factory_PluginFactory::createPlugin($plugin->getName(), array(), array('websiteUrl' => $data['websiteUrl']));
					if(method_exists($pluginInstance, $data['method'])) {
						$pluginInstance->$data['method']();
					}
				}
				catch (Exceptions_SeotoasterException $se) {
					error_log($se->getMessage());
					error_log($se->getTraceAsString());
				}
			}, array('method' => $method, 'websiteUrl' => $websiteHelper->getUrl()));
		}

	}
}

