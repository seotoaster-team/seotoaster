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
        array_push($this->_cacheTags, __CLASS__);
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
		$this->_view->categoriesList = Application_Model_Mappers_PageMapper::getInstance()->fetchMainCategories();
        $this->_view->useImage       = (in_array('img', $this->_options) || in_array('imgc', $this->_options)) ? true : false;
        $this->_view->crop           = (in_array('imgc', $this->_options) || in_array('crop', $this->_options)) ? true : false;
        $class                       = current(preg_grep('/class=*/', $this->_options));
        $this->_view->listClass      = ($class !== null) ? preg_replace('/class=/', '', $class) : '';
		$this->_addCacheTags($this->_view->categoriesList);
		return $this->_view->render('categories.phtml');
	}

	private function _renderPagesList() {
        $key = key(preg_grep('/class=*/', $this->_options));
        if(isset($this->_options[1]) && $this->_options[1] !== 'img' && $this->_options[1] !== 'imgc' && $this->_options[1] !== 'crop' && $this->_options[1] !== 'ajax' && $key !== 1){
            $categoryName = $this->_options[1];
        } else{
            $categoryName = $this->_toasterOptions['navName'];
        }
        $this->_view->pagesList = $this->_findPagesListByCategoryName($categoryName);
        $this->_view->useImage  = (in_array('img', $this->_options) || in_array('imgc', $this->_options)) ? true : false;
        $this->_view->crop      = (in_array('imgc', $this->_options) || in_array('crop', $this->_options)) ? true : false;
        $class                  = current(preg_grep('/class=*/', $this->_options));
        $this->_view->listClass = ($class !== null) ? preg_replace('/class=/', '', $class) : '';
        if(end($this->_options) == 'ajax') {
            $this->_view->ajax = true;
            $this->_view->categoryName = $categoryName;
        } else {$this->_view->ajax = false;}
        $this->_view->pageId = $this->_toasterOptions['id'];
        $this->_addCacheTags($this->_view->pagesList);
        return $this->_view->render('pages.phtml');
	}

	private function _findPagesListByCategoryName($categoryName) {
        $pageMapper = Application_Model_Mappers_PageMapper::getInstance();
		$page       = $pageMapper->findByNavName($categoryName);
		if(!$page instanceof Application_Model_Models_Page) {
			throw new Exceptions_SeotoasterWidgetException('There is no category with such name: ' . $categoryName);
		}
		return Application_Model_Mappers_PageMapper::getInstance()->findByParentId(($page->getParentId() > 0) ? $page->getParentId() : $page->getId());
	}

	private function _addCacheTags($pagesList){
		if (is_array($pagesList) && !empty($pagesList)){
			foreach ($pagesList as $page) {
				array_push($this->_cacheTags, 'pageid_'.$page->getId());
			}
		}
	}

	public static function getAllowedOptions() {
		$translator = Zend_Registry::get('Zend_Translate');
		return array(
			array(
				'alias'  => $translator->translate('List all categories'),
				'option' => 'list:categories'
			),
			array(
				'alias'  => $translator->translate('List all categories (with images)'),
				'option' => 'list:categories:img'
			),
			array(
				'alias'  => $translator->translate('List all categories (with crop images)'),
				'option' => 'list:categories:imgc'
			),
			array(
				'alias'  => $translator->translate('List all pages for current category'),
				'option' => 'list:pages'
			),
			array(
				'alias'  => $translator->translate('List all pages for current category (with images)'),
				'option' => 'list:pages:img'
			),
			array(
				'alias'  => $translator->translate('List all pages for current category (with crop images)'),
				'option' => 'list:pages:imgc'
			),
			array(
				'alias'  => $translator->translate('List all pages for category'),
				'option' => 'list:pages:category_name'
			),
			array(
				'alias'  => $translator->translate('List all pages for category (with images)'),
				'option' => 'list:pages:category_name:img'
			),
			array(
				'alias'  => $translator->translate('List all pages for category (with crop images)'),
				'option' => 'list:pages:category_name:imgc'
			)
		);

		//return array('list:categories', 'list:categories:img', 'list:pages', 'list:pages:img', 'list:pages:category_name', 'list:pages:category_name:img');
	}
}