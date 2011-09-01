<?php

class Widgets_Gal_Gal extends Widgets_Abstract {

	const DEFAULT_THUMB_SIZE = '250';

	private $_websiteHelper  = null;

	protected function  _init() {
		parent::_init();
		$this->_view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));
		$this->_websiteHelper    = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$this->_view->websiteUrl = $this->_websiteHelper->getUrl();
	}

	protected function  _load() {
		if(!is_array($this->_options) || empty($this->_options) || !isset($this->_options[0]) || !$this->_options[0] || preg_match('~^\s*$~', $this->_options[0])) {
			throw new Exceptions_SeotoasterException('You should specify folder.');
		}

		$path       = $this->_websiteHelper->getPath() . $this->_websiteHelper->getMedia() . $this->_options[0] . '/';
		$thumbSize  = isset($this->_options[1]) ? $this->_options[1] : self::DEFAULT_THUMB_SIZE;
		$useCrop    = isset($this->_options[2]) ? (boolean)$this->_options[2] : false;
		$useCaption = isset($this->_options[3]) ? (boolean)$this->_options[3] : false;

		if(!is_dir($path)) {
			throw new Exceptions_SeotoasterException($path . ' is not a directory.');
		}

		$sourceImages = Tools_Filesystem_Tools::scanDirectory($path . 'original/');
		$galFolder    = $path . (($useCrop) ? 'crop/' : 'thumbnails/');

		if(!is_dir($galFolder)) {
			 @mkdir($galFolder);
		}

		foreach ($sourceImages as $image) {
			if(is_file($galFolder . $image)) {
				$imgInfo = getimagesize($galFolder . $image);
				if($imgInfo[0] != $thumbSize) {
					Tools_Image_Tools::resize($path . 'original/' . $image, $thumbSize, !($useCrop), $galFolder, $useCrop);
				}
			}
			else {
				Tools_Image_Tools::resize($path . 'original/' . $image, $thumbSize, !($useCrop), $galFolder, $useCrop);
			}
		}
		$this->_view->folder        = $this->_options[0];
		$this->_view->original      = str_replace($this->_websiteHelper->getPath(), $this->_websiteHelper->getUrl(), $path) . 'original/';
		$this->_view->images        = $sourceImages;
		$this->_view->useCaption    = $useCaption;
		$this->_view->galFolderPath = $path . $galFolder;
		$this->_view->galFolder     = str_replace($this->_websiteHelper->getPath(), $this->_websiteHelper->getUrl(), $galFolder);
		return $this->_view->render('gallery.phtml');
	}

	public static function getWidgetMakerContent() {
		$view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));

		$data = array(
			'title'   => 'Image Gallery',
			'content' => $view->render('wmcontent.phtml')
		);

		unset($view);
		return $data;
	}
}

