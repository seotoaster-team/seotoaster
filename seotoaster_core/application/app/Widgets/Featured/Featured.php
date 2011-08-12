<?php

/**
 * Featured widget. Takes care about featured:area, featured:page, etc...
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_Featured_Featured extends Widgets_Abstract {

	const AREA_DESC_LENGTH = '250';

	protected function  _init() {
		parent::_init();
		$this->_view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));
		$website = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$this->_view->websiteUrl = $website->getUrl();
	}

	protected function _load() {
		$featuredType = array_shift($this->_options);
		$rendererName = '_renderFeatured' . ucfirst($featuredType);
		if(method_exists($this, $rendererName)) {
			return $this->$rendererName($this->_options);
		}
		throw new Exceptions_SeotoasterWidgetException($this->_translator->translate('Wrong featured type'));
	}

	private function _renderFeaturedArea($params) {
		if(!is_array($params) || empty($params) || !isset($params[0]) || !$params[0] || preg_match('~^\s*$~', $params[0])) {
			throw new Exceptions_SeotoasterWidgetException($this->_translator->translate('Featured area name required.'));
		}

		$areaName             = $params[0];
		$useImages            = (isset($params[1]) && $params[1]) ? true : false;
		$maxDescriptionLength = (isset($params[2]) && intval($params[2])) ? intval($params[2]) : self::AREA_DESC_LENGTH;
		$faMapper             = new Application_Model_Mappers_FeaturedareaMapper();
		$featuredArea         = $faMapper->findByName($areaName);

		if($featuredArea === null) {
			return $this->_translator->translate('Featured area ') . $areaName . $this->_translator->translate(' does not exist');
		}

		$this->_view->faPages                 = $featuredArea->getPages();
		$this->_view->faId                    = $featuredArea->getId();
		$this->_view->faName                  = $featuredArea->getName();
		$this->_view->faPageDescriptionLength = $maxDescriptionLength;

		return $this->_view->render('area.phtml');
	}

	private function _renderFeaturedPage($params) {
		if(!is_array($params) || empty($params) || !isset($params[0]) || !$params[0] || preg_match('~^\s*$~', $params[0])) {
			throw new Exceptions_SeotoasterWidgetException($this->_translator->translate('Featured page id required.'));
		}
		$pageMapper = new Application_Model_Mappers_PageMapper();
		$page       = $pageMapper->find(intval($params[0]));
		if($page === null) {
			throw new Exceptions_SeotoasterWidgetException($this->_translator->translate('Page with such id is not found'));
		}

		$this->_view->pagePreviewImage = '';
		$this->_view->descLength       = (isset($params[2]) && intval($params[2])) ? intval($params[2]) : self::AREA_DESC_LENGTH;
		$this->_view->page             = $page;
		return $this->_view->render('page.phtml');
	}

}