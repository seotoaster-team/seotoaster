<?php

class Zend_View_Helper_ToasterLink extends Zend_View_Helper_Abstract {

	public function toasterLink($controller, $action, $linkText, $params = '', $hrefOnly = false) {
		$websiteData = Zend_Registry::get('website');
		$controller  = (substr($controller, 0, 7) == 'backend') ? 'backend/' . $controller : $controller;
		$linkText    = $this->view->translate($linkText);
		$href        = $websiteData['url'] . $controller . '/' . $action . '/' . (($params) ? $params : '');
		$link = '<a class="tpopup" href="javascript:;" url="' . $href . '" title="' . $linkText . '">' . $linkText . '</a>';
		if($hrefOnly) {
			return $href;
		}
		return $link;
	}
}

