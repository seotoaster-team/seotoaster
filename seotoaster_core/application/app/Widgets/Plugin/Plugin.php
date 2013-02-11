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
				return $this->_translator->translate('You need install the ') . $plugin->getName() . $this->_translator->translate(' plug-in to view and use this great feature.'). ' <a href="http://www.seotoaster.com/website-plugins-marketplace.html" target="_blank">'.$this->_translator->translate('Download plug-ins here').' '.'</a>'.$this->_translator->translate('and watch a short video to learn how to install plug-ins on your website'). ' <a href="http://www.seotoaster.com/how-to-add-a-plugin.html" target="_blank">'.$this->_translator->translate('here').'</a>.';
			}
			try {
				$toasterPlugin = Tools_Factory_PluginFactory::createPlugin($plugin->getName(), $this->_options, $this->_toasterOptions);
				return $toasterPlugin->run();
			}
			catch (Exceptions_SeotoasterPluginException $spe) {
                if(Tools_System_Tools::debugMode()) {
                    error_log($spe->getMessage() . "\n" . $spe->getTraceAsString());
                }
                if(Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_ADMINPANEL)) {
				    return $spe->getMessage();
                }
			}
            catch(Exceptions_SeotoasterException $se) {
                if(Tools_System_Tools::debugMode()) {
                    error_log($se->getMessage() . "\n" . $se->getTraceAsString());
                }
                if(Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_ADMINPANEL)) {
                    return $se->getMessage();
                }
            }
			catch (Exception $e) {
                if(Tools_System_Tools::debugMode()) {
                    error_log($e->getMessage() . "\n" . $e->getTraceAsString());
                }
				//return $e->getMessage();
			}
		}
        if(Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_ADMINPANEL)) {
            return $this->_translator->translate('You need the') . ' '. $pluginName . ' '. $this->_translator->translate('plug-in to view and use this great feature.'). ' <a href="http://www.seotoaster.com/website-plugins-marketplace.html" target="_blank">'.$this->_translator->translate('Download plug-ins here').' '.'</a>'. $this->_translator->translate('and watch a short video to learn how to install plug-ins on your website'). ' <a href="http://www.seotoaster.com/how-to-add-a-plugin.html" target="_blank">'.$this->_translator->translate('here').'</a>.';
        }
		return '';
	}
}

