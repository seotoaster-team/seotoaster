<?php

class Widgets_StaticMenu_StaticMenu extends Widgets_Abstract {

	protected function  _init() {
		parent::_init();
		$this->_view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));
		$websiteData = Zend_Registry::get('website');
		$this->_view->websiteUrl = $websiteData['url'];
	}

	protected function  _load() {
		$pageMapper = new Application_Model_Mappers_PageMapper();
		$pages      = $pageMapper->fetchAllStaticMenuPages();
		if(empty($pages)) {
			return '';
		}
		$this->_view->staticPages = $pages;
		return $this->_view->render('staticmenu.phtml');
	}

	public static function getAllowedOptions() {
		return array('staticMenu');
	}
}