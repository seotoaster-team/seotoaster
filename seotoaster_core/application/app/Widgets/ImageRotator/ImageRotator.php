<?php

class Widgets_ImageRotator_ImageRotator extends Widgets_Abstract {

	protected function  _init() {
		parent::_init();
		$this->_cacheable = false;
	}

	protected function  _load() {

	}

	public static function getWidgetMakerContent() {
		$data = array(
			'title'   => 'Image Rotator',
			'content' => 'Img rotator content'
		);
		return $data;
	}
}

