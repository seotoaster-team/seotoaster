<?php
/**
 * Description of PageController
 *
 * @author iamne
 */
class Backend_PageController extends Zend_Controller_Action {

	public function init() {
		parent::init();
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_PAGES)) {
			$this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
		}
		$this->view->websiteUrl = $this->_helper->website->getUrl();
		$this->_helper->layout->disableLayout();
	}

	public function pageAction() {
		$pageForm   = new Application_Form_Page();
		$pageId     = $this->getRequest()->getParam('id');
		$mapper     = new Application_Model_Mappers_PageMapper();

		$page = ($pageId) ? $mapper->find($pageId) : new Application_Model_Models_Page();

		$categoriesOptions = $mapper->selectCategoriesIdName();
		$pageForm->getElement('pageCategory')->addMultiOptions($categoriesOptions);

		if(!$this->getRequest()->isPost()) {
			if($page instanceof Application_Model_Models_Page) {
				$pageForm->setOptions($page->toArray());
			}
		}
		else {
			if($pageForm->isValid($this->getRequest()->getParams())) {
				$pageData = $pageForm->getValues();
				$page->setOptions($pageData);
				$page->setTargetedKey($page->getH1());
				$page->setParentId($pageData['pageCategory']);
				$page->setShowInMenu($pageData['inMenu']);
				$mapper->save($page);
			}
			exit;
		}
		$this->view->pageForm = $pageForm;
	}
}

