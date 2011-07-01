<?php

/**
 * Featured widget. Takes care about featured:area, featured:page, etc...
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_Featured_Featured extends Widgets_Abstract {

	const TYPE_AREA = 'area';

	const TYPE_PAGE = 'page';

	protected function  _init() {
		parent::_init();
		$this->_view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));
		$website = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$this->_view->websiteUrl = $website->getUrl();
	}

	protected function _load() {
		$featuredType = $this->_options[0];
		switch ($featuredType) {
			case self::TYPE_AREA:
				return $this->_renderFeaturedArea();
				//return 'Featured area';
			break;
			case self::TYPE_PAGE:
				return 'Featured page';
			break;
			default:
				return 'Bad featured option';
			break;
		}
	}

	private function _renderFeaturedArea() {
		$areaName             = $this->_options[1];
		$useImages            = $this->_options[2];
		$maxDescriptionLength = $this->_options[3];
		$faMapper             = new Application_Model_Mappers_FeaturedareaMapper();
		$featuredArea         = $faMapper->findByName($areaName);

		if($featuredArea === null) {
			return 'Featured area <stron>' . $areaName . '</strong> does not exist';
		}

		$this->_view->faPages = $featuredArea->getPages();
		$this->_view->faId    = $featuredArea->getId();
		$this->_view->faName  = $featuredArea->getName();

		return $this->_view->render('area.phtml');
	}

}