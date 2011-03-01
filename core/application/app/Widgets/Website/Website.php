<?php
/**
 * Description of Website
 *
 * @author iamne
 */
class Widgets_Website_Website extends Widgets_Abstract {

	const OPT_URL = 'url';

	protected function  _load() {
		$content = '';
		$type    = $this->_options[0];
		switch ($type) {
			case self::OPT_URL:
				$content = $this->_toasterOptions['websiteUrl'];
			break;
		}
		return $content;
	}

	public static function getAllowedOptions() {
		return array('website:url');
	}

}

