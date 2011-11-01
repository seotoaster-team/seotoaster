<?php

/**
 * Menu
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_Menu_Menu extends Widgets_Abstract {

	protected function  _init() {
		parent::_init();
		$this->_view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));
		$website = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$this->_view->websiteUrl = $website->getUrl();
	}

	protected function  _load() {
		$menuType     = $this->_options[0];
		$rendererName = '_render' . ucfirst($menuType) . 'Menu';
		if(method_exists($this, $rendererName)) {
			return $this->$rendererName();
		}
		throw new Exceptions_SeotoasterException('Can not render <strong>' . $menuType . '</strong> menu.');
	}

	private function _renderMainMenu() {
		$pagesList  = array();
		$pages = Application_Model_Mappers_PageMapper::getInstance()->fetchAllMainMenuPages();
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
		$this->_view->pages = $pagesList;
		return $this->_view->render('mainmenu.phtml');
	}

	private function _renderStaticMenu() {
		$this->_view->staticPages = Application_Model_Mappers_PageMapper::getInstance()->fetchAllStaticMenuPages();
		unset($pageMapper);
		return $this->_view->render('staticmenu.phtml');
	}

	public static function getAllowedOptions() {
		$translator = Zend_Registry::get('Zend_Translate');
		return array(
			array(
				'alias'   => $translator->translate('Main menu'),
				'options' => 'menu:main'
			),
			array(
				'alias'   => $translator->translate('Static menu'),
				'options' => 'menu:static'
			)
		);
	}

}

