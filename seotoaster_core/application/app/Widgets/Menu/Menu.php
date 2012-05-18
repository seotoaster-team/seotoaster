<?php

/**
 * Menu
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_Menu_Menu extends Widgets_Abstract {

	protected function  _init() {
		parent::_init();
		$this->_cacheId   = strtolower(__CLASS__).(!empty($this->_options)?'-'.implode('-', $this->_options):'');
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
        foreach($pages as $key => $page) {
            if($page['parentId'] == 0) {
                if($page['protected'] && !$isAllowed && !$showMemberPages) {
                    continue;
                }
                $pagesList[$key]['category'] = $page;
                foreach($pages as $subPage) {
                    if($subPage['protected'] && !$isAllowed && !$showMemberPages) {
                        continue;
                    }
                    if($subPage['parentId'] == $page['id']) {
                        $pagesList[$key]['subPages'][] = $subPage;
                    }
                }
            }
        }
        $this->_view->pages = $pagesList;
        return $this->_view->render('mainmenu.phtml');
	}

	private function _renderFlatMenu() {
        $flatMenuPages = Application_Model_Mappers_PageMapper::getInstance()->fetchAllStaticMenuPages();
        if($flatMenuPages && is_array($flatMenuPages) && !empty($flatMenuPages)) {
            $this->_view->staticPages = Application_Model_Mappers_PageMapper::getInstance()->fetchAllStaticMenuPages();
            return $this->_view->render('staticmenu.phtml');
        }
        return '';
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

