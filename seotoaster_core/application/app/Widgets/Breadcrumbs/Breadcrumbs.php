<?php

/**
 * Breadcrumbs
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_Breadcrumbs_Breadcrumbs extends Widgets_Abstract {

	protected function _load() {
		$separator     = (isset($this->_options[0]) ? trim($this->_options[0]) : '&raquo;');
		$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$pageMapper    = Application_Model_Mappers_PageMapper::getInstance();
		$page          = $pageMapper->find($this->_toasterOptions['id']);
		if($page->getIs404page()) {
			return;
		}
		$crumbs[] = '<a href="' . $websiteHelper->getUrl() . '" title="' . $this->_translator->translate('Home') . '">' . $this->_translator->translate('Home') . '</a>';
		if($page->getParentId() > 0) {
			$parentPage = $pageMapper->find($page->getParentId());
			$crumbs[]   = '<a href="' . $parentPage->getUrl() . '" title="' . $parentPage->getH1() . '">' . $parentPage->getNavName() . '</a>';
		}
		//$crumbs[] = '<a href="' . $page->getUrl() . '" title="' . $page->getH1() . '">' . $page->getNavName() . '</a>';
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

