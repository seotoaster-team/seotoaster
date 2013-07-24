<?php

/**
 * Featured widget. Takes care about featured:area, featured:page, etc...
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_Featured_Featured extends Widgets_Abstract {

	const AREA_DESC_LENGTH   = '250';

	const AREA_PAGES_COUNT   = '5';

	const FEATURED_TYPE_PAGE = 'page';

	const FEATURED_TYPE_AREA = 'area';

	protected function  _init() {
		parent::_init();
		$this->_view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));
		$website = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$this->_view->websiteUrl = $website->getUrl();
		//checking if its area and random
		if (!empty($this->_options) && (reset($this->_options) === self::FEATURED_TYPE_AREA ) && (1 === intval(end($this->_options)))){
			$this->_cacheable = false;
		}
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
		$pagesCount           = (isset($params[1]) && $params[1]) ? $params[1] : self::AREA_PAGES_COUNT;
		$maxDescriptionLength = (isset($params[2]) && is_numeric($params[2])) ? intval($params[2]) : self::AREA_DESC_LENGTH;
		$random               = (intval(end($params)) === 1) ? true : false;

		$featuredArea         = Application_Model_Mappers_FeaturedareaMapper::getInstance()->findByName($areaName);

		if($featuredArea === null) {
            if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_ADMINPANEL)) {
                return '';
            }
			return $this->_translator->translate('Featured area ') . $areaName . $this->_translator->translate(' does not exist');
		}

		$featuredArea->setLimit($pagesCount)
			->setRandom($random);
		$this->_view->useImage                = (isset($params[3]) && ($params[3] == 'img' || $params[3] == 'imgc')) ? $params[3] : false;
		$this->_view->faPages                 = $featuredArea->getPages();
		$this->_view->faId                    = $featuredArea->getId();
		$this->_view->faName                  = $featuredArea->getName();
		$this->_view->faPageDescriptionLength = $maxDescriptionLength;

		// adding cache tag for this fa
		array_push($this->_cacheTags, 'fa_'.$areaName);
        array_push($this->_cacheTags, 'pageTags');
        $areaPages = $featuredArea->getPages();
		foreach ($areaPages as $page){
			array_push($this->_cacheTags, 'pageid_'.$page->getId());
		}
		return $this->_view->render('area.phtml');
	}

	private function _renderFeaturedPage($params) {
		if(!is_array($params) || empty($params) || !isset($params[0]) || !$params[0] || preg_match('~^\s*$~', $params[0])) {
			throw new Exceptions_SeotoasterWidgetException($this->_translator->translate('Featured page id required.'));
		}
		$page = Application_Model_Mappers_PageMapper::getInstance()->find(intval($params[0]));
		if($page === null) {
			throw new Exceptions_SeotoasterWidgetException($this->_translator->translate('Page with such id is not found'));
		}
		$this->_view->useImage         = (isset($params[2]) && ($params[2] == 'img' || $params[2] == 'imgc')) ? $params[2] : false;
		$this->_view->descLength       = (isset($params[1]) && is_numeric($params[1])) ? intval($params[1]) : self::AREA_DESC_LENGTH;
		$this->_view->page             = $page;
		array_push($this->_cacheTags, 'pageid_'.$page->getId());
		return $this->_view->render('page.phtml');
	}


	public static function getWidgetMakerContent() {
		$translator = Zend_Registry::get('Zend_Translate');
		$view       = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));

		$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$data = array(
			'title'   => $translator->translate('List pages by tags'),
			'icons'   => array(
				$websiteHelper->getUrl() . 'system/images/widgets/featured.png',
			),
			'content' => $view->render('wmcontent.phtml')
		);

		unset($view);
		unset($translator);
		return $data;
	}
}