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
		$this->_helper->AjaxContext()->addActionContext('edit404page', 'json')->initContext('json');
		$this->_helper->AjaxContext()->addActionContext('rendermenu', 'json')->initContext('json');
	}

	public function pageAction() {
		$pageForm   = new Application_Form_Page();
		$pageId     = $this->getRequest()->getParam('id');
		$mapper     = new Application_Model_Mappers_PageMapper();

		$pageForm->getElement('pageCategory')->addMultiOptions(array('Categories' => $mapper->selectCategoriesIdName()));

		$page = ($pageId) ? $mapper->find($pageId) : new Application_Model_Models_Page();

		if(!$this->getRequest()->isPost()) {
			if($page instanceof Application_Model_Models_Page) {
				$pageForm->setOptions($page->toArray());
				$pageForm->getElement('pageId')->setValue($page->getId());
			}
		}
		else {
			$params = $this->getRequest()->getParams();
			if($pageForm->isValid($this->getRequest()->getParams())) {
				//saving old data for seo routine
				$this->_helper->session->oldPageUrl = $page->getUrl();
				$this->_helper->session->oldPageH1  = $page->getH1();

				$page->registerObserver(new Tools_Seo_Watchdog());

				$pageData = $pageForm->getValues();
				$page->setOptions($pageData);
				$page->setUrl($this->_helper->page->validate($pageData['url']));
				$page->setTargetedKey($page->getH1());
				$page->setParentId($pageData['pageCategory']);
				$page->setShowInMenu($pageData['inMenu']);
				$mapper->save($page);

				$page->notifyObservers();
				$this->_helper->response->success(array('redirectTo' => $page->getUrl()));
				exit;
			}
			$this->_helper->response->fail(Tools_Content_Tools::proccessFormMessagesIntoHtml($pageForm->getMessages(), get_class($pageForm)));
			exit;
		}
		$this->view->pageForm = $pageForm;
	}

	public function deleteAction() {
		if($this->getRequest()->isPost()) {
			$pageMapper = new Application_Model_Mappers_PageMapper();
			$page       = $pageMapper->find(intval($this->getRequest()->getParam('id')));

			$page->registerObserver(new Tools_Page_GarbageCollector(array(
				'action' => Tools_System_GarbageCollector::CLEAN_ONDELETE
			)))->registerObserver(new Tools_Seo_Watchdog());

			$this->_helper->response->success($pageMapper->delete($page));
		}
	}

	public function rendermenuAction() {
		$menuType    = $this->getRequest()->getParam('mtype');

		$menuOptions = array();
		$menuHtml    = '';

		$mapper      = new Application_Model_Mappers_PageMapper();

		switch ($menuType) {
			case Application_Model_Models_Page::IN_MAINMENU:
				$categories = $mapper->selectCategoriesIdName();
				$categories[Application_Model_Models_Page::IDCATEGORY_PRODUCT] = 'Product pages';
				$menuOptions = array('Seotoaster' => array(strval(Application_Model_Models_Page::IDCATEGORY_CATEGORY) => 'This page is a category'), 'Categories' => $categories);
			break;
			case Application_Model_Models_Page::IN_STATICMENU:
				$menuOptions= array(Application_Model_Models_Page::IDCATEGORY_DEFAULT => 'Make your selection');
			break;
			case Application_Model_Models_Page::IN_NOMENU:
				$menuOptions = array('No menu options' => array(
					Application_Model_Models_Page::IDCATEGORY_DEFAULT => 'No menu',
					Application_Model_Models_Page::IDCATEGORY_DRAFT   => 'Draft'
				));
			break;
		}
		$selectHelper = $this->view->getHelper('formSelect');
		$this->view->select = $selectHelper->formSelect('pageCategory', '', null, $menuOptions);
	}

	public function edit404pageAction() {
		$pageMapper = new Application_Model_Mappers_PageMapper();
		$notFoundPage = $pageMapper->find404Page();
		$this->view->notFoundUrl = ($notFoundPage instanceof Application_Model_Models_Page) ? $notFoundPage->getUrl() : '';
	}
}

