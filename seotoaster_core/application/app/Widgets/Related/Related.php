<?php

/**
 * Related widget
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_Related_Related extends Widgets_Abstract {

	const REL_WORD_COUNT  = '2';

	const REL_DESC_LENGTH = '250';

	const REL_USEIMAGE    = false;

	protected function  _init() {
		parent::_init();
		$this->_view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));
		$website = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$this->_view->websiteUrl = $website->getUrl();
	}


	protected function _load() {
		$keywordCount = (isset($this->_options[0]) ? $this->_options[0] : self::REL_WORD_COUNT);
		$keywords     = $this->_prepareKeywords($this->_toasterOptions['metaKeywords']);
		$currPageId   = $this->_toasterOptions['id'];
		$related      = array();
		if(sizeof($keywords) >= sizeof($keywordCount)) {
			$pages = Application_Model_Mappers_PageMapper::getInstance()->fetchAll('id != ' . $currPageId);
			foreach ($pages as $page) {
				$pageKeywords = $this->_prepareKeywords($page->getMetaKeywords());
				if(sizeof(array_intersect($keywords, $pageKeywords)) >= $keywordCount) {
					$related[] = $page;
				}
			}
		}
		//$this->_view->descLength = (isset($this->_options[1])) ? $this->_options[1] : self::REL_DESC_LENGTH;
		$this->_view->descLength = self::REL_DESC_LENGTH;
		$this->_view->useImg     = (isset($this->_options[2])) ? $this->_options[2] : self::REL_USEIMAGE;
		$this->_view->related    = ($this->_options[1] >= sizeof($related)) ? $related : array_slice($related, 0, $this->_options[1]);
		return $this->_view->render('related.phtml');
	}

	private function _prepareKeywords($keywords) {
		return array_map(function($value) {
			return trim($value);
		}, explode(',', $keywords));
	}

	public static function getWidgetMakerContent() {
		$view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));

		$data = array(
			'title'   => 'Related pages list',
			'content' => $view->render('wmcontent.phtml')
		);

		unset($view);
		return $data;
	}
}

