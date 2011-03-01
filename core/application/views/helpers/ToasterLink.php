<?php

class Zend_View_Helper_ToasterLink extends Zend_View_Helper_Abstract {

	public function toasterLink($controller, $action, $linkText, $useThinkBox = array(), $hrefOnly = false) {
		$websiteData = Zend_Registry::get('website');
		$tbTail      = '?TB_iframe=true&height=%height%&width=%width%';
		$controller  = (substr($controller, 0, 7) == 'backend') ? 'backend/' . $controller : $controller;
		$linkText    = $this->view->translate($linkText);
		$href        = $websiteData['url'] . $controller . '/' . $action;
		if(is_array($useThinkBox) && !empty($useThinkBox)) {
			$tbTail = str_replace('%height%', $useThinkBox[0], $tbTail);
			if(isset($useThinkBox[1])) {
				$tbTail = str_replace('%width%', $useThinkBox[1], $tbTail);
			}
			$href .= $tbTail;
			$link  = '<a href="javascript:;" onclick="tb_show(\'\', \'' . $href . '\', \'\')" title="' . $linkText . '">' . $linkText . '</a>';
		}
		else {
			$link = '<a href="' . $href . '" title="' . $linkText . '">' . $linkText . '</a>';
		}
		if($hrefOnly) {
			return $href;
		}
		return $link;
	}
}

