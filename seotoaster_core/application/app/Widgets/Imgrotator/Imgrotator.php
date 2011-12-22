<?php

/**
 * Imgrotator {$imgrotator:folder:slideshow/notslideshow:time(if slideshow):maxwidth:maxheight}
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_Imgrotator_Imgrotator extends Widgets_Abstract {

	/**
	 * Default swap time in seconds
	 *
	 */
	const DEFAULT_SWAP_TIME     = '2';

	const DEFAULT_SLIDER_WIDTH  = '250';

	const DEFAULT_SLIDER_HEIGHT = 'auto';

	const DEFAULT_SWAP_EFFECT   = 'fade';

	const DEFAULT_PICS_FOLDER   = 'original';

	private $_websiteHelper     = null;

	protected function  _init() {
		parent::_init();
		$this->_view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));
		$this->_websiteHelper    = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$this->_view->websiteUrl = $this->_websiteHelper->getUrl();
	}

	protected function _load() {
		if(!is_array($this->_options) || empty($this->_options) || !isset($this->_options[0]) || !$this->_options[0] || preg_match('~^\s*$~', $this->_options[0])) {
			throw new Exceptions_SeotoasterException($this->_translator->translate('You should specify folder.'));
		}

		$imageFolder  = self::DEFAULT_PICS_FOLDER;
		$sliderWidth  = (isset($this->_options[3]) && $this->_options[3]) ? $this->_options[3] : self::DEFAULT_SLIDER_WIDTH;
		$configHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
		if($sliderWidth <= $configHelper->getConfig('imgSmall')) {
			$imageFolder = 'small';
		}
		elseif($sliderWidth <= $configHelper->getConfig('imgMedium')) {
			$imageFolder = 'medium';
		}
		elseif($sliderWidth <= $configHelper->getConfig('imgLarge')) {
			$imageFolder = 'large';
		}
		$fullPathToPics    = $this->_websiteHelper->getPath() . $this->_websiteHelper->getMedia() . $this->_options[0] . '/' . $imageFolder . '/';
		$this->_view->uniq         = uniqid('rotator-');
		$this->_view->files        = Tools_Filesystem_Tools::scanDirectory($fullPathToPics, false, false);
		$this->_view->sliderWidth  = (isset($this->_options[3]) && $this->_options[3]) ? $this->_options[3] : self::DEFAULT_SLIDER_WIDTH;
		$this->_view->sliderHeight = (isset($this->_options[4]) && $this->_options[4]) ? $this->_options[4] : self::DEFAULT_SLIDER_HEIGHT;
		$this->_view->swapTime     = (isset($this->_options[2]) && $this->_options[2]) ? $this->_options[2] : self::DEFAULT_SWAP_TIME;;
		$this->_view->slideShow    = (isset($this->_options[1]) && $this->_options[1]) ? true : false;
		$this->_view->folder       = $this->_options[0] . '/'. $imageFolder . '/';
		$this->_view->effect       = (isset($this->_options[5]) && $this->_options[5]) ? $this->_options[5] : self::DEFAULT_SWAP_EFFECT;
		return $this->_view->render('rotator.phtml');
	}


	public static function getWidgetMakerContent() {
		$translator = Zend_Registry::get('Zend_Translate');
		$view       = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));

		$data = array(
			//'title'   => $translator->translate('Image rotator'),
			'content' => $view->render('wmcontent.phtml')
		);

		unset($view);
		unset($translator);
		return $data;
	}

}

