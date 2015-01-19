<?php

/**
 * List widget. Creates a list of categories or list of pages that you have on your website.
 * {$list:categories} - return list of pages in main menu category
 * {$list:pages:[:category_name|img|imgc|crop|ajax|class='class']} - return list of pages by category name
 *
 * category_name - page category name
 * img - with page preview image
 * imgc - with page preview crop image
 * ajax - ajax content loading
 * class - add class
 *
 * Class Widgets_List_List
 */
class Widgets_List_List extends Widgets_Abstract {

    /**
     * Exclude options for list pages widget
     *
     * @var array
     */
    protected $_excludeOptions = array('img', 'imgc', 'crop', 'ajax');

    protected function  _init() {
		parent::_init();
		$this->_view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));
		$website = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$this->_view->websiteUrl = $website->getUrl();
        array_push($this->_cacheTags, __CLASS__);
	}

    /**
     * @return mixed
     * @throws Exceptions_SeotoasterException
     */
    protected function  _load() {
		$listType     = $this->_options[0];
		$rendererName = '_render' . ucfirst($listType) . 'List';
		if(method_exists($this, $rendererName)) {
			return $this->$rendererName();
		}
		throw new Exceptions_SeotoasterException('Can not render <strong>' . $listType . '</strong> list.');
	}

    /**
     * Render category pages list
     *
     * @return string
     */
    private function _renderCategoriesList() {
		$this->_view->categoriesList = Application_Model_Mappers_PageMapper::getInstance()->fetchMainCategories();
        $this->_view->useImage       = (in_array('img', $this->_options) || in_array('imgc', $this->_options)) ? true : false;
        $this->_view->crop           = (in_array('imgc', $this->_options) || in_array('crop', $this->_options)) ? true : false;
        $class                       = current(preg_grep('/class=*/', $this->_options));
        $this->_view->listClass      = ($class !== null) ? preg_replace('/class=/', '', $class) : '';
		$this->_addCacheTags($this->_view->categoriesList);
		return $this->_view->render('categories.phtml');
	}

    /**
     * Render category pages list by category name
     *
     * @return string
     */
    private function _renderPagesList() {
        $key = key(preg_grep('/class=*/', $this->_options));
        if(isset($this->_options[1]) && !in_array($this->_options[1], $this->_excludeOptions) && $key !== 1){
            $categoryName = $this->_options[1];
        } else{
            $categoryName = $this->_toasterOptions['navName'];
        }
        $this->_view->pagesList = $this->_findPagesListByCategoryName($categoryName);
        $this->_view->useImage  = (in_array('img', $this->_options) || in_array('imgc', $this->_options)) ? true : false;
        $this->_view->crop      = (in_array('imgc', $this->_options) || in_array('crop', $this->_options)) ? true : false;
        $class                  = current(preg_grep('/class=*/', $this->_options));
        $this->_view->listClass = ($class !== null) ? preg_replace('/class=/', '', $class) : '';
        $this->_view->ajax = false;
        if(end($this->_options) == 'ajax') {
            $this->_view->ajax = true;
            $this->_view->categoryName = $categoryName;
        }
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

    /**
     * Add cache tags for each page in list
     *
     * @param $pagesList
     */
    private function _addCacheTags($pagesList){
		if (is_array($pagesList) && !empty($pagesList)){
			foreach ($pagesList as $page) {
				array_push($this->_cacheTags, 'pageid_'.$page->getId());
			}
		}
	}

    /**
     * Options for widget maker
     *
     * @return array
     */
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

	}
}