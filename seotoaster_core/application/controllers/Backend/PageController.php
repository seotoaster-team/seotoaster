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

				$pageData = $pageForm->getValues();

				//if we'r creating page -> check that we do not have an identical urls
				if(!$pageId) {
					$pageExists = $mapper->findByUrl($this->_helper->page->validate($pageData['url']));
					if($pageExists instanceof Application_Model_Models_Page) {
						$this->_helper->response->fail('Page with url <strong>' . $this->_helper->page->validate($pageData['url']) . '</strong> already exists.');
						exit;
					}
				}

				//saving old data for seo routine
				$this->_helper->session->oldPageUrl = $page->getUrl();
				$this->_helper->session->oldPageH1  = $page->getH1();

				$page->registerObserver(new Tools_Seo_Watchdog());

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

	/**
	 * @todo Optimize this!
	 */
	public function rendermenuAction() {
		$menuType    = $this->getRequest()->getParam('mtype');
		$pageId      = $this->getRequest()->getParam('pId');

		$menuOptions = array();
		$menuHtml    = '';

		$mapper      = new Application_Model_Mappers_PageMapper();

		switch ($menuType) {
			case Application_Model_Models_Page::IN_MAINMENU:
				$categories = $mapper->selectCategoriesIdName();
				$menuOptions = array(
					'-4'         => 'Make your selection',
					'Seotoaster' => array(
						Application_Model_Models_Page::IDCATEGORY_CATEGORY => 'This page is a category',
						Application_Model_Models_Page::IDCATEGORY_PRODUCT  => 'Product pages'
					),
					'Categories' => $categories
				);
			break;
			case Application_Model_Models_Page::IN_STATICMENU:
				$menuOptions= array(Application_Model_Models_Page::IDCATEGORY_DEFAULT => 'Make your selection');
			break;
			case Application_Model_Models_Page::IN_NOMENU:
				$menuOptions = array('-4' => 'Make your selection', 'No menu options' => array(
					Application_Model_Models_Page::IDCATEGORY_DEFAULT => 'This page is in no menu',
					Application_Model_Models_Page::IDCATEGORY_DRAFT   => 'This page is in draft'
				));
			break;
		}
		$selectHelper       = $this->view->getHelper('formSelect');

		if($pageId) {
			$mapper   = new Application_Model_Mappers_PageMapper();
			$currPage = $mapper->find($pageId);
		}

		$this->view->select = $selectHelper->formSelect('pageCategory', (isset($currPage) ? $currPage->getParentId() : ''), null, $menuOptions);
	}

	public function edit404pageAction() {
		$pageMapper = new Application_Model_Mappers_PageMapper();
		$notFoundPage = $pageMapper->find404Page();
		$this->view->notFoundUrl = ($notFoundPage instanceof Application_Model_Models_Page) ? $notFoundPage->getUrl() : '';
	}

	public function draftAction() {
		$pageMapper             = new Application_Model_Mappers_PageMapper();
		//@todo can be added to the cache but not critical
		$this->view->draftPages = $pageMapper->fetchAllDraftPages();
		unset($pageMapper);
	}

	public function organizeAction() {
		$pageMapper = new Application_Model_Mappers_PageMapper();
		$tree = array();
		$categories = $pageMapper->findByParentId(0);
		if(is_array($categories) && !empty ($categories)) {
			foreach ($categories as $category) {
				$tree[] = array(
					'category' => $category,
					'pages'    => $pageMapper->findByParentId($category->getId())
				);
			}
			$this->view->tree = $tree;
		}
	}
}

