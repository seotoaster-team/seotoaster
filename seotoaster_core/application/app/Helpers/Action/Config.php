<?php

class Helpers_Action_Config extends Zend_Controller_Action_Helper_Abstract {

	private $_config = array();

	public function init() {
		try {
			$this->_config = Zend_Registry::get('extConfig');
		}
		catch (Zend_Exception $ze) {
			if(empty($this->_config)) {
				$configTable   = new Application_Model_DbTable_Config();
				$this->_config = $configTable->selectConfig();
				Zend_Registry::set('extConfig', $this->_config);
			}
		}
	}

	public function getConfig($name = '') {
		if($name) {
			return (isset($this->_config[$name]) ? $this->_config[$name] : '');
		}
		return $this->_config;
	}

}

