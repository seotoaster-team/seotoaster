<?php

class Widgets_Plugin_Plugin extends Widgets_Abstract {

	protected $_cacheable = false;

	protected function _load() {
		$pluginName   = strtolower(array_shift($this->_options));
		if(!$pluginName) {
			return $this->_translator->translate('Plugin name not specified.');
		}
		$plugin       = Application_Model_Mappers_PluginMapper::getInstance()->findByName($pluginName);
		if($plugin !== null) {
			if($plugin->getStatus() != Application_Model_Models_Plugin::ENABLED) {
				return $this->_translator->translate('Plugin ') . $plugin->getName() . $this->_translator->translate(' is not installed.');
			}
			try {
				$toasterPlugin = Tools_Factory_PluginFactory::createPlugin($plugin->getName(), $this->_options, $this->_toasterOptions);
				return $toasterPlugin->run();
			}
			catch (Exceptions_SeotoasterPluginException $spe) {
                if(Tools_System_Tools::debugMode()) {
                    error_log($spe->getMessage() . "\n" . $spe->getTraceAsString());
                }
				//return $spe->getMessage();
			}
			catch (Exception $e) {
                if(Tools_System_Tools::debugMode()) {
                    error_log($e->getMessage() . "\n" . $e->getTraceAsString());
                }
				//return $e->getMessage();
			}
		}
        if(Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_ADMINPANEL) && Tools_System_Tools::debugMode()) {
            return $this->_translator->translate('Cannot load ' . $pluginName . ' plugin');
        }
		return '';
	}
}

