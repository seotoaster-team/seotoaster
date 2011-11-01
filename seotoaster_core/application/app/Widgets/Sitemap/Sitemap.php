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
		$pagesList    = array();
		$pages        = Application_Model_Mappers_PageMapper::getInstance()->fetchAllMainMenuPages();
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
		$this->_view->config = Application_Model_Mappers_ConfigMapper::getInstance()->getConfig();
		$this->_view->pages = $pagesList;
		return $this->_view->render('sitemap.phtml');
	}

	public static function getAllowedOptions() {
		$translator = Zend_Registry::get('Zend_Translate');
		return array(
			array(
				'alias'   => $translator->translate('Site map'),
				'options' => 'sitemap'
			)
		);
	}
}

