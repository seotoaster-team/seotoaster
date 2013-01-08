<?php

/**
 * Zend framework plugin to register seotoaster plugins routes and hooks
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Plugins_Plugin extends Zend_Controller_Plugin_Abstract {

    /**
     * Seotoaster before controller plugin hook name
     *
     */
    const PREDISPATCH_METHOD  = 'beforeController';

    /**
     * Seotoaster after controller plugin hook name
     *
     */
    const POSTDISPATCH_METHOD = 'afterController';

    /**
     * Seotoaster before router plugin hook name
     *
     */
    const BOFOREROUTER_METHOD = 'beforeRouter';

    /**
     * Register Seotoaster plugins routes and hooks
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function routeStartup(Zend_Controller_Request_Abstract $request) {
		$sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('session')->init();
		$cacheHelper   = Zend_Controller_Action_HelperBroker::getStaticHelper('cache')->init();
		if(!isset($sessionHelper->pluginRoutesFetched) || $sessionHelper->pluginRoutesFetched !== true) {
			Tools_Plugins_Tools::fetchPluginsRoutes();
			$sessionHelper->pluginRoutesFetched = true;
		}
		Tools_Plugins_Tools::registerPluginsIncludePath();
        //trigger plugins before router starts
        $this->_triggerToasterPlugins(self::BOFOREROUTER_METHOD);
    }

    /**
     * Trigger pre dispatch plugins hooks
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request) {
		$this->_triggerToasterPlugins(self::PREDISPATCH_METHOD);
	}

    /**
     * Trigger post dispatch plugins hooks
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function postDispatch(Zend_Controller_Request_Abstract $request) {
		$this->_triggerToasterPlugins(self::POSTDISPATCH_METHOD);
		//replace http with https for internal links if requested via https
		if ($request->isSecure()){
			$websiteConfig = Zend_Registry::get('website');
			$body          = strtr($this->_response->getBody(), array(
		        Zend_Controller_Request_Http::SCHEME_HTTP . '://' . $websiteConfig['url'] => Zend_Controller_Request_Http::SCHEME_HTTPS . '://' . $websiteConfig['url']
			)) ;
			$this->_response->setBody($body);
		}
	}

    /**
     * Trigger Seotoaster plugins hooks
     *
     * @param $method string Method to trigger
     */
    private function _triggerToasterPlugins($method) {
		$enabledPlugins = Tools_Plugins_Tools::getEnabledPlugins();
		if(is_array($enabledPlugins) && !empty ($enabledPlugins)) {
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

