<?php

class Widgets_Seo_Seo extends Widgets_Abstract {

	const OPT_TOP    = 'top';

	const OPT_BOTTOM = 'bottom';

	protected function  _load() {
		$content = '';
		$param   = $this->_options[0];
		switch ($param) {
			case self::OPT_TOP:
				$content = 'SEOTOP CONTENT';
			break;
			case self::OPT_BOTTOM:
				$content = 'SEOBOTTOM CONTENT';
			break;
		}
		return $content;
	}

	public static function getAllowedOptions() {
		return array('seo:top', 'seo:bottom');
	}

}

