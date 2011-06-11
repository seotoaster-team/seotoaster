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
	}

	public function pageAction() {
		$pageForm   = new Application_Form_Page();
		$pageId     = $this->getRequest()->getParam('id');
		$mapper     = new Application_Model_Mappers_PageMapper();

		$page = ($pageId) ? $mapper->find($pageId) : new Application_Model_Models_Page();

		$categoriesOptions = array('Categories' => $mapper->selectCategoriesIdName());
		$pageForm->getElement('pageCategory')->addMultiOptions($categoriesOptions);

		if(!$this->getRequest()->isPost()) {
			if($page instanceof Application_Model_Models_Page) {
				$pageForm->setOptions($page->toArray());
				$pageForm->getElement('pageId')->setValue($page->getId());
			}
		}
		else {
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

	public function edit404pageAction() {
		$pageMapper = new Application_Model_Mappers_PageMapper();
		$notFoundPage = $pageMapper->find404Page();
		$this->view->notFoundUrl = ($notFoundPage instanceof Application_Model_Models_Page) ? $notFoundPage->getUrl() : '';
	}
}

