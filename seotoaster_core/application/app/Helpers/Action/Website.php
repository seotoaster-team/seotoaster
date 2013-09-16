<?php

class Helpers_Action_Website extends Zend_Controller_Action_Helper_Abstract {

    const DEFAULT_PAGE = 'index.html';

	public function getUrl() {
		$url = preg_replace('~^https?://~', '', $this->_getParam('url'));
		return Zend_Controller_Front::getInstance()->getRequest()->getScheme() . '://' . $url;
	}

	public function getDefaultPage() {
        return self::DEFAULT_PAGE;
    }

    public function  __call($name, $arguments) {
        $name = str_replace('get', '', $name);
        if(($param = $this->_getParam(strtolower($name))) == '') {
            return $this->_getParam(lcfirst($name));
        }
        return $param;
	}

	private function _getParam($name) {
		$websiteData = Zend_Registry::get('website');
		return isset($websiteData[$name]) ? $websiteData[$name] : '';
	}
}

