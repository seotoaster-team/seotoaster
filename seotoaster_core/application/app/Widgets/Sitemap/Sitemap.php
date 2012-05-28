<?php

/**
 * Sitemap
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_Sitemap_Sitemap extends Widgets_Abstract {

	protected function  _init() {
		parent::_init();
		$this->_view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));
		$website = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$this->_view->websiteUrl = $website->getUrl();
	}

	protected function _load() {
        $pagesList       = array();
        $pages           = Application_Model_Mappers_PageMapper::getInstance()->fetchAllMainMenuPages();
        $configHelper    = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
        $showMemberPages = (boolean) $configHelper->getConfig('memPagesInMenu');
        $isAllowed       = Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_PAGE_PROTECTED);
        $flatPages       = Application_Model_Mappers_PageMapper::getInstance()->fetchAllStaticMenuPages();
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
        $this->_view->pages      = $pagesList;
        $this->_view->flatPages  = $flatPages;
		$this->_view->newsFolder = $configHelper->getConfig('newsFolder');
		return $this->_view->render('sitemap.phtml');
	}

	public static function getAllowedOptions() {
		$translator = Zend_Registry::get('Zend_Translate');
		return array(
			array(
				'alias'   => $translator->translate('Site map'),
				'option' => 'sitemap'
			)
		);
	}
}

