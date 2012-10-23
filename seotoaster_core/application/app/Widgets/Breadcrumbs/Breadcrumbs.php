<?php

/**
 * Breadcrumbs
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_Breadcrumbs_Breadcrumbs extends Widgets_Abstract {

    protected $_cacheable = false;
    
	protected function _load() {
		$separator     = (isset($this->_options[0]) ? trim($this->_options[0]) : '&raquo;');
		$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
		$pageMapper    = Application_Model_Mappers_PageMapper::getInstance();
		$page          = $pageMapper->find($this->_toasterOptions['id']);
        $breadCrumbList = array();
		if($page->getIs404page()) {
			return;
		}
        $crumbs[] = '<a href="' . $websiteHelper->getUrl() . '" title="' . $this->_translator->translate('Home') . '">' . $this->_translator->translate('Home') . '</a>';
        if($page->getUrl() == 'index.html'){
            $sessionHelper->breadCrumbList = $breadCrumbList;
			return '<div class="breadcrumbs">' . implode(' ' . $separator . ' ', $crumbs) . '</div>';
        }
        $breadcrumb = '<a href="' . $page->getUrl() . '" title="' . $page->getH1() . '">' . $page->getNavName() . '</a>';
        if(isset($sessionHelper->breadCrumbList)){
            $breadCrumbList = $sessionHelper->breadCrumbList;
        }
        $breadCrumbsQuantity = count($breadCrumbList);
        $crumbs = array_merge($crumbs, $breadCrumbList);
        if(isset($sessionHelper->previousPageId) && $sessionHelper->previousPageId != $this->_toasterOptions['id'] && $breadCrumbsQuantity != 2){
            $breadCrumbList[] = $breadcrumb;
        }elseif($breadCrumbsQuantity == 2){
            array_shift($breadCrumbList);
            $breadCrumbList[] = $breadcrumb;
        }elseif($breadCrumbsQuantity != 2){
            $breadCrumbList[] = $breadcrumb;
        }
        
        $sessionHelper->previousPageId = $this->_toasterOptions['id'];
        $sessionHelper->breadCrumbList = $breadCrumbList;
        
		$crumbs[] = $page->getNavName();
		return '<div class="breadcrumbs">' . implode(' ' . $separator . ' ', $crumbs) . '</div>';
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

