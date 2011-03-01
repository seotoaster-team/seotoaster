<?php
/**
 * $page widget
 *
 * @author iamne
 */
class Widgets_Page_Page extends Widgets_Abstract {

	const OPT_H1    = 'h1';

	const OPT_ID    = 'id';

	const OPT_TITLE = 'title';

	protected function  _load() {
		$content = '';
		$type    = $this->_options[0];
		switch ($type) {
			case self::OPT_H1:
				$content = $this->_toasterOptions['h1'];
			break;
			case self::OPT_TITLE:
				$content = $this->_toasterOptions['headerTitle'];
			break;
			case self::OPT_ID:
				$content = $this->_toasterOptions['id'];
			break;
		}
		return $content;
	}

	public static function getAllowedOptions() {
		return array('page:id', 'page:h1', 'page:title');
	}

}

