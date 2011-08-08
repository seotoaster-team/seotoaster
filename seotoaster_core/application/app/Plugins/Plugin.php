<?php

/**
 * Plugin
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Plugins_Plugin extends Zend_Controller_Plugin_Abstract {

	const PREDISPATCH_METHOD  = 'beforeController';
	const POSTDISPATCH_METHOD = 'afterController';

	public function preDispatch(Zend_Controller_Request_Abstract $request) {
		$this->_triggerToasterPlugins(self::PREDISPATCH_METHOD);
	}

	public function postDispatch(Zend_Controller_Request_Abstract $request) {
		$this->_triggerToasterPlugins(self::POSTDISPATCH_METHOD);
	}

	private function _triggerToasterPlugins($method) {
		$pluginMapper   = new Application_Model_Mappers_PluginMapper();
		$enabledPlugins = $pluginMapper->findEnabled();

		if(is_array($enabledPlugins) && !empty ($enabledPlugins)) {
			$miscData      = Zend_Registry::get('misc');
			$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Website');
			foreach ($enabledPlugins as $plugin) {
				$pluginClassName = ucfirst($plugin->getName());
				$pluginPath      = $websiteHelper->getPath() . $miscData['pluginsPath'] . $plugin->getName() . '/' . $pluginClassName . '.php';
				if(file_exists($pluginPath)) {
					require_once $pluginPath;
					if(method_exists($pluginClassName, $method)) {
						$pluginsData[] = $pluginClassName::$method();
					}
				}
			}
		}
	}
}

