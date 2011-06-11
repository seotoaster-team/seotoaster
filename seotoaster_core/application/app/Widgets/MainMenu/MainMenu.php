<?php

/**
 * Description of MainMenu
 *
 * @author iamne
 */
class Widgets_MainMenu_MainMenu extends Widgets_Abstract {

	protected function  _init() {
		parent::_init();
		$this->_view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));
		$websiteData = Zend_Registry::get('website');
		$this->_view->websiteUrl = $websiteData['url'];
	}

	protected function  _load() {
		$this->_view->pages = $this->_prepareContent();
		return $this->_view->render('mainmenu.phtml');
	}

	private function _prepareContent() {
		$pagesList  = array();
		$pageMapper = new Application_Model_Mappers_PageMapper();
		$pages = $pageMapper->fetchAllMainMenuPages();
		foreach ($pages as $key => $page) {
			if($page->getParentId() == 0) {
				$pagesList[$key]['category'] = $page;
				foreach ($pages as $subPage) {
					if($subPage->getParentId() == $page->getId()) {
						$pagesList[$key]['subPages'][] = $subPage;
					}
				}
			}
		}
		return $pagesList;
	}

	public static function getAllowedOptions() {
		return array('mainMenu');
	}
}

