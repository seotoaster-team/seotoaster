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
	}

	protected function  _load() {
        $website      = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$menuType     = $this->_options[0];
		$rendererName = '_render' . ucfirst($menuType) . 'Menu';
        $this->_view->websiteUrl = $website->getUrl();
		if(method_exists($this, $rendererName)) {
			return $this->$rendererName();
		}
		throw new Exceptions_SeotoasterException('Can not render <strong>' . $menuType . '</strong> menu.');
	}

	private function _renderMainMenu() {
        $pagesList       = array();
        $pages           = Application_Model_Mappers_PageMapper::getInstance()->fetchAllMainMenuPages();
        $configHelper    = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
        $showMemberPages = (boolean) $configHelper->getConfig('memPagesInMenu');
        $isAllowed       = Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_PAGE_PROTECTED);
        foreach ($pages as $key => $page) {
            if($page->getParentId() == 0) {
                if($page->getResourceId() == Tools_Security_Acl::RESOURCE_PAGE_PROTECTED && !$isAllowed && !$showMemberPages) {
                    continue;
                }

                $pagesList[$key]['category'] = $page;
                foreach ($pages as $subPage) {
                    if($subPage->getResourceId() == Tools_Security_Acl::RESOURCE_PAGE_PROTECTED && !$isAllowed && !$showMemberPages) {
                        continue;
                    }
                    if($subPage->getParentId() == $page->getId()) {
                        $pagesList[$key]['subPages'][] = $subPage;
                    }
                }
            }
        }
        $this->_view->pages = $pagesList;
        return $this->_view->render('mainmenu.phtml');
	}

	private function _renderFlatMenu() {
		$this->_view->staticPages = Application_Model_Mappers_PageMapper::getInstance()->fetchAllStaticMenuPages();
		unset($pageMapper);
		return $this->_view->render('staticmenu.phtml');
	}

	public static function getAllowedOptions() {
		$translator = Zend_Registry::get('Zend_Translate');
		return array(
			array(
				'alias'   => $translator->translate('Main menu'),
				'option' => 'menu:main'
			),
			array(
				'alias'   => $translator->translate('Flat menu'),
				'option' => 'menu:flat'
			)
		);
	}

}

