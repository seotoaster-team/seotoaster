<?php

/**
 * News
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_News_News extends Widgets_Abstract {

	protected function  _init() {
		parent::_init();
		$this->_view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));
		$website = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$this->_view->websiteUrl = $website->getUrl();
	}

	protected function  _load() {
		$widgetType   = array_shift($this->_options);
		$rendererName = '_renderNews' . ucfirst($widgetType);
		if(method_exists($this, $rendererName)) {
			return $this->$rendererName($this->_options);
		}
		throw new Exceptions_SeotoasterWidgetException($this->_translator->translate('Wrong widget type'));
	}

	private function _renderNewsItem() {
		$currentPageId         = $this->_toasterOptions['id'];
		$mapper                = Application_Model_Mappers_NewsMapper::getInstance();
		$newsItem = $mapper->find($currentPageId);
		if($newsItem === null) {
			return;
		}
		$this->_view->newsItem = $newsItem;
		return $this->_view->render('item.phtml');
	}


	private function _renderNewsList() {
		//news list should render only on news index page and NOT news pages
		//bacause of specific structure of the news template
		if(isset($this->_toasterOptions['news']) && $this->_toasterOptions['news']) {
			return;
		}
		$newsList       = array();
		$mapper         = Application_Model_Mappers_NewsMapper::getInstance();
		$newsCatMapper  = Application_Model_Mappers_NewsCategoryMapper::getInstance();
		$listByCategory = (isset ($this->_options[0])) ? strtolower($this->_options[0]) : false;
		if($listByCategory) {
			$newsList[] = $newsCatMapper->findByName($listByCategory, true);
		}
		else {
			$newsList = $newsCatMapper->fetchAll();
			//fetching news pages without category
			//making facke category 'General'
			$generalCat     = new Application_Model_Models_NewsCategory();
			$generalCatNews = $mapper->fetchAll("news = '1'", array('last_update'), true);
			foreach ($generalCatNews as $key => $newsItem) {
				if($newsItem->getCategories() != array()) {
					unset ($generalCatNews[$key]);
				}
			}
			$generalCat->setName($this->_translator->translate('General'))
				->setNewsItems($generalCatNews);
			$newsList[] = $generalCat;
		}
		$configHelper            = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
		$this->_view->newsFolder = $configHelper->getConfig('newsFolder');
		$this->_view->newsList   = $newsList;
		return $this->_view->render('list.phtml');
	}

	private function _renderNewsScroller() {
		//only news that are featured will go to the news scroller
		$allNews = Application_Model_Mappers_NewsMapper::getInstance()->fetchAll("news = '1'", array('last_update'), true);
		if(is_array($allNews) && !empty ($allNews)) {
			foreach ($allNews as $key => $newsItem) {
				if(!$newsItem->getFeatured()) {
					unset ($allNews[$key]);
				}
			}
			$configHelper            = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
			$this->_view->newsList   = $allNews;
			$this->_view->newsFolder = $configHelper->getConfig('newsFolder');
			return $this->_view->render('scroller.phtml');
		}
	}

	public static function getAllowedOptions() {
		return array('news:list', 'news:scroller', 'news:category_name');
	}
}

