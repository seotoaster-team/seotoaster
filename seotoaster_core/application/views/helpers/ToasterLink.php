<?php

class Zend_View_Helper_ToasterLink extends Zend_View_Helper_Abstract {

	const WSIZE_LARGE  = 'large';

	const WSIZE_MEDIUM = 'medium';

	const WSIZE_SMALL  = 'small';

	public function toasterLink($controller, $action, $linkText, $params = '', $hrefOnly = false, $winSizeType = self::WSIZE_LARGE) {
		$websiteData = Zend_Registry::get('website');
		$controller  = 'backend/' . ((substr($controller, 0, 7) != 'backend') ? 'backend_' . $controller : $controller);
		$linkText    = $this->view->translate($linkText);

		if(is_array($params)) {
			$params = implode('/', $params);
		}

		$winsize = $this->_getValidWinSize($winSizeType);

		$href = $websiteData['url'] . $controller . '/' . $action . '/' . (($params) ? $params : '');
		$link = '<a class="tpopup" href="javascript:;" data-pwidth="' . $winsize['width'] . '" data-pheight="' . $winsize['height'] . '" data-url="' . $href . '" title="' . $linkText . '">' . $linkText . '</a>';
		if($hrefOnly) {
			return $href;
		}
		return $link;
	}

	private function _getValidWinSize($winSizeType = self::WSIZE_LARGE) {
		$params = array();
		switch ($winSizeType) {
			case self::WSIZE_LARGE:
				$params = array(
					'width'  => 960,
					'height' => 650
				);
			break;
			case self::WSIZE_MEDIUM:
				$params = array(
					'width'  => 480,
					'height' => 650
				);
			break;
			case self::WSIZE_SMALL:
				$params = array(
					'width'  => 960,
					'height' => 650
				);
			break;
			default:
				if (is_array($winSizeType) && isset($winSizeType['width']) && isset($winSizeType['height'])){
					$params = array(
						'width' => $winSizeType['width'],
						'height' => $winSizeType['height'],
					);
				}
			break;
		}
		return $params;
	}
}

