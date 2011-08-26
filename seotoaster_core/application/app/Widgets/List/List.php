<?php

/**
 * List
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_List_List extends Widgets_Abstract {

	protected function  _init() {
		parent::_init();
		$this->_view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));
		$website = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$this->_view->websiteUrl = $website->getUrl();
	}

	protected function  _load() {
		$listType     = $this->_options[0];
		$rendererName = '_render' . ucfirst($listType) . 'List';
		if(method_exists($this, $rendererName)) {
			return $this->$rendererName();
		}
		throw new Exceptions_SeotoasterException('Can not render <strong>' . $listType . '</strong> list.');
	}

	private function _renderCategoriesList() {
		$this->_view->categoriesList = Application_Model_Mappers_PageMapper::getInstance()->findByParentId(0);
		$this->_view->useImage       = (isset($this->_options[1]) && $this->_options[1]) ? true : false;
		return $this->_view->render('categories.phtml');
	}

	private function _renderPagesList() {
		return (isset($this->_options[1]) && $this->_options[1] !== 'img') ? $this->_renderPagesListByCategoryName() : $this->_renderCurrentCategoryPagesList();
	}

	private function _renderCurrentCategoryPagesList() {
		$categoryName = $this->_toasterOptions['navName'];
		$this->_view->pagesList = $this->_findPagesListByCategoryName($categoryName);
		$this->_view->useImage  = (isset($this->_options[1]) && $this->_options[1]) ? true : false;
		return $this->_view->render('pages.phtml');
	}

	private function _renderPagesListByCategoryName() {
		$categoryName = $this->_options[1];
		$this->_view->pagesList = $this->_findPagesListByCategoryName($categoryName);
		$this->_view->useImage  = (isset($this->_options[2]) && $this->_options[2]) ? true : false;
		return $this->_view->render('pages.phtml');
	}

	private function _findPagesListByCategoryName($categoryName) {
		$category   = Application_Model_Mappers_PageMapper::getInstance()->findByNavName($categoryName);
		if(!$category instanceof Application_Model_Models_Page) {
			throw new Exceptions_SeotoasterWidgetException('There is no category with such name: ' . $categoryName);
		}
		return Application_Model_Mappers_PageMapper::getInstance()->findByParentId($category->getId());
	}

	public static function getAllowedOptions() {
		return array('list:categories', 'list:categories:img', 'list:pages', 'list:pages:img', 'list:pages:category_name', 'list:pages:category_name:img');
	}
}

