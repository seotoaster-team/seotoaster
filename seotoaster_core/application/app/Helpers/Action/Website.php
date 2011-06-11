<?php

class Helpers_Action_Website extends Zend_Controller_Action_Helper_Abstract {

	public function  __call($name, $arguments) {
		$name = strtolower(str_replace('get', '', $name));
		return $this->_getParam($name);
	}

	private function _getParam($name) {
		$websiteData = Zend_Registry::get('website');
		return isset($websiteData[$name]) ? $websiteData[$name] : '';
	}
}

