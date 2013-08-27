<?php

/**
 * Breadcrumbs
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_Breadcrumbs_Breadcrumbs extends Widgets_Abstract {

    protected $_cacheable     = false;

    protected $_websiteHelper = null;

    protected $_sessionHelper = null;

    protected $_translator    = null;

    protected function _init() {
        $this->_websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $this->_sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
        $this->_translator    = Zend_Controller_Action_HelperBroker::getStaticHelper('language');
        $this->_view          = new Zend_View(array('scriptPath' => __DIR__ . '/views'));
    }
    
	protected function _load() {

		$separator     = (isset($this->_options[0]) ? trim($this->_options[0]) : '&raquo;');
		$pageMapper    = Application_Model_Mappers_PageMapper::getInstance();
		$page          = $pageMapper->find($this->_toasterOptions['id']);
        $homePage      = $pageMapper->findByUrl($this->_websiteHelper->getDefaultPage());

        $breadCrumbList = array();
		if($page->getIs404page()) {
			return;
		}
        $crumbs[] = '<a href="' . $this->_websiteHelper->getUrl() . '" title="' . $homePage->getH1() . '">' . $homePage->getNavName() . '</a>';
        if($page->getUrl() == 'index.html'){
            $this->_sessionHelper->breadCrumbList = $breadCrumbList;
			return '<div class="breadcrumbs">' . implode(' ' . $separator . ' ', $crumbs) . '</div>';
        }
        $breadcrumb = '<a href="' . $page->getUrl() . '" title="' . $page->getH1() . '">' . $page->getNavName() . '</a>';
        if(isset($this->_sessionHelper->breadCrumbList)){
            $breadCrumbList = $this->_sessionHelper->breadCrumbList;
        }
        $breadCrumbsQuantity = count($breadCrumbList);
        $crumbs = array_merge($crumbs, $breadCrumbList);
        if(isset($this->_sessionHelper->previousPageId) && $this->_sessionHelper->previousPageId != $this->_toasterOptions['id'] && $breadCrumbsQuantity != 2){
            $breadCrumbList[] = $breadcrumb;
        }elseif($breadCrumbsQuantity == 2 && end($breadCrumbList) != $breadcrumb){
            array_shift($breadCrumbList);
            $breadCrumbList[] = $breadcrumb;
        }elseif($breadCrumbsQuantity != 2 && end($breadCrumbList) != $breadcrumb){
            $breadCrumbList[] = $breadcrumb;
        }

        $this->_sessionHelper->previousPageId = $this->_toasterOptions['id'];
        $this->_sessionHelper->breadCrumbList = $breadCrumbList;
        
        if(end($crumbs) != $breadcrumb){
            $crumbs[] = $page->getNavName();
        }else{
            array_pop($crumbs);
            $crumbs[] = $page->getNavName();
        }
		return '<div class="breadcrumbs">' . implode(' ' . $separator . ' ', $crumbs) . '</div>';
	}

    protected function _renderClassic() {
        $defaultPageUrl = Zend_Controller_Action_HelperBroker::getStaticHelper('website')->getUrl();
        $crumbs = array(
            $this->_translator->translate('Home') => $defaultPageUrl
        );
        if(($this->_toasterOptions['url'] != $defaultPageUrl) && ($this->_toasterOptions['parentId'] > 0)) {
            $categoryPage = Application_Model_Mappers_PageMapper::getInstance()->find($this->_toasterOptions['parentId']);
            if(!$categoryPage instanceof Application_Model_Models_Page) {
                return $crumbs;
            }
            $crumbs[$categoryPage->getNavName()] = $categoryPage->getUrl();
        } else {
            $crumbs[$this->_toasterOptions['navName']] = $this->_toasterOptions['url'];
        }
        $this->_view->crumbs = $crumbs;
        return $this->_view->render('crumbs.phtml');
    }

	public static function getAllowedOptions() {
		$translator = Zend_Registry::get('Zend_Translate');
		return array(
			array(
				'alias'   => $translator->translate('Breadcrumbs'),
				'option' => 'breadcrumbs'
			)
		);
	}
}

