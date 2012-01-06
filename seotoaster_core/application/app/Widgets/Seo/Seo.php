<?php

class Widgets_Seo_Seo extends Widgets_Abstract {

	protected function  _load() {
		$widgetType   = array_shift($this->_options);
		$rendererName = '_renderSeo' . ucfirst($widgetType);
		if(method_exists($this, $rendererName)) {
			return $this->$rendererName($this->_options);
		}
		throw new Exceptions_SeotoasterWidgetException($this->_translator->translate('Wrong seo widget type'));
	}

//	public static function getAllowedOptions() {
//		$translator = Zend_Registry::get('Zend_Translate');
//		return array(
//			array(
//				'alias'   => $translator->translate('Seo top content'),
//				'option' => 'seo:top'
//			),
//			array(
//				'alias'   => $translator->translate('Seo bottom content'),
//				'option' => 'seo:bottom'
//			)
//		);
//	}

	private function _renderSeoTop() {
		return '';
	}

	private function _renderSeoBottom() {
		return '';
	}
}

