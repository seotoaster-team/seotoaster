<?php

class Widgets_Seo_Seo extends Widgets_Abstract {

	const OPT_TOP    = 'top';

	const OPT_BOTTOM = 'bottom';

	protected function  _load() {
		$widgetType   = array_shift($this->_options);
		$rendererName = '_renderSeo' . ucfirst($widgetType);
		if(method_exists($this, $rendererName)) {
			return $this->$rendererName($this->_options);
		}
		throw new Exceptions_SeotoasterWidgetException($this->_translator->translate('Wrong seo widget type'));
	}

	public static function getAllowedOptions() {
		return array('seo:top', 'seo:bottom');
	}

	private function _renderSeoTop() {
		return 'SEO TOP';
	}

	private function _renderSeoBottom() {
		return 'SEO BOTTOM';
	}
}

