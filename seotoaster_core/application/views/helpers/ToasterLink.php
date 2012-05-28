<?php

class Zend_View_Helper_ToasterLink extends Zend_View_Helper_Abstract {

	const WSIZE_LARGE  = 'large';

	const WSIZE_MEDIUM = 'medium';

	const WSIZE_SMALL  = 'small';

	/**
	 * Generates a link wrapped in <a /> tag according to a given controller/action that will be opened in popup
	 * @param $controller Controller name
	 * @param $action Action name
	 * @param $linkText Link message. Will be passed to translator
	 * @param string $params
	 * @param bool $hrefOnly If true - return only url
	 * @param string $winSizeType
	 * @return string
	 */
	public function toasterLink($controller, $action, $linkText, $params = '', $hrefOnly = false, $winSizeType = self::WSIZE_LARGE) {
		$linkText      = htmlspecialchars($this->view->translate($linkText));
		$winsize       = $this->_getValidWinSize($winSizeType);

		switch ($controller){
			case (strpos($controller, 'backend') === 0):
				$routeParams = array(
					'controller' => $controller,
					'action'     => $action
				);
				$routeName = 'backend';
				break;
			case 'plugin':
				$routeParams = array(
					'name' => $action
				);
				$routeName = 'pluginroute';
				break;
			default:
				$routeParams = array(
					'controller' => 'backend_' . $controller,
					'action'     => $action
				);
				$routeName = 'backend';
				break;
		}
		if (is_array($params)) {
			$routeParams = array_merge($routeParams, $params);
		}
		$scheme = Zend_Controller_Front::getInstance()->getRequest()->getScheme();
		$host = Zend_Controller_Front::getInstance()->getRequest()->getHttpHost();
		$href = $scheme.'://'.$host. $this->view->url($routeParams, $routeName) .(is_string($params)?'/'.$params:null);
		$link = '<a class="tpopup ' . strtolower($action) . '" href="javascript:;" data-pwidth="' . $winsize['width'] . '" data-pheight="' . $winsize['height'] . '" data-url="' . $href . '" title="' . $linkText . '">' . $linkText . '</a>';
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
					'width'  => 964,
					'height' => 644
				);
			break;
			default:
				if (is_array($winSizeType) && isset($winSizeType['width']) && isset($winSizeType['height'])){
					return array(
						'width' => $winSizeType['width'],
						'height' => $winSizeType['height'],
					);
				}
			case self::WSIZE_MEDIUM:
				$params = array(
					'width'  => 480,
					'height' => 649
				);
			break;
			case self::WSIZE_SMALL:
				$params = array(
					'width'  => 350,
					'height' => 354
				);
			break;
		}
		return $params;
	}
}

