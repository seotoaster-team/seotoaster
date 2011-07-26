<?php

/**
 * SeoController
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Backend_SeoController extends Zend_Controller_Action {

	public function init() {
		parent::init();
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_PAGES)) {
			$this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
		}
		$this->_helper->AjaxContext()->addActionContext('loaddeeplinkslist', 'json')->initContext('json');
		$this->_helper->AjaxContext()->addActionContext('loadredirectslist', 'json')->initContext('json');
		$this->_helper->AjaxContext()->addActionContext('removeredirect', 'json')->initContext('json');
		$this->_helper->AjaxContext()->addActionContext('removedeeplink', 'json')->initContext('json');
		$this->view->websiteUrl = $this->_helper->website->getUrl();
	}

	public function robotsAction() {
		$robotsForm = new Application_Form_Robots();
		if(!$this->getRequest()->isPost()) {
			$robotstxtContent = Tools_Filesystem_Tools::getFile('robots.txt');
			$robotsForm->setContent($robotstxtContent);
		}
		else {
			if($robotsForm->isValid($this->getRequest()->getParams())) {
				$robotsData = $robotsForm->getValues();
				try{
					Tools_Filesystem_Tools::saveFile('robots.txt', $robotsData['content']);
					$this->_helper->response->success('Robots.txt updated.');
				}
				catch (Exception $e) {
					$this->_helper->response->fail($e->getMessage());
				}
			}
		}
		$this->view->form = $robotsForm;
	}

	public function redirectsAction() {
		$redirectForm   = new Application_Form_Redirect();
		$pageMapper     = new Application_Model_Mappers_PageMapper();
		$redirectMapper = new Application_Model_Mappers_RedirectMapper();

		$redirectForm->setToasterPages($pageMapper->fetchIdUrlPairs());

		if(!$this->getRequest()->isPost()) {
			$this->view->redirects = $redirectMapper->fetchRedirectMap();
		}
		else {
			if($redirectForm->isValid($this->getRequest()->getParams())) {
				$data     = $redirectForm->getValues();
				$redirect = new Application_Model_Models_Redirect();
				$redirect->setFromUrl(Tools_System_Tools::getUrlPath($data['fromUrl']));
				$redirect->setDomainFrom(Tools_System_Tools::getUrlScheme($data['fromUrl']) . '://' . Tools_System_Tools::getUrlHost($data['fromUrl']) . '/');
				if(intval($data['toUrl'])) {
					$page = $pageMapper->find($data['toUrl']);
					$redirect->setDomainTo($this->_helper->website->getUrl());
					$redirect->setToUrl($page->getUrl());
					$redirect->setPageId($page->getId());
				}
				else {
					$redirect->setDomainTo(Tools_System_Tools::getUrlScheme($data['toUrl']) . '://' . Tools_System_Tools::getUrlHost($data['toUrl']) . '/');
					$redirect->setToUrl(Tools_System_Tools::getUrlPath($data['toUrl']));
					$redirect->setPageId(null);
				}
				$redirectMapper->save($redirect);
				$this->_helper->cache->clean('toaster_301redirects', '301redirects');
				$this->_helper->response->success('Redirect saved');
			}
			else {
				$this->_helper->response->fail(Tools_Content_Tools::proccessFormMessagesIntoHtml($redirectForm->getMessages(), get_class($redirectForm)));
				exit;
			}
		}
		$this->view->form = $redirectForm;
	}

	public function loadredirectslistAction() {
		$redirectMapper = new Application_Model_Mappers_RedirectMapper();
		$redirects      = $redirectMapper->fetchAll(null, array('id'));
		$this->view->redirects = array_reverse($redirects);
		$this->view->redirectsList = $this->view->render('backend/seo/loadredirectslist.phtml');
	}

	public function removeredirectAction() {
		if($this->getRequest()->isPost()) {
			$ids            = $this->getRequest()->getParam('id');
			$redirectMapper = new Application_Model_Mappers_RedirectMapper();
			if(is_array($ids)) {
				foreach ($ids as $id) {
					$redirectMapper->delete($redirectMapper->find($id));
				}
			}
			else {
				$redirectMapper->delete($redirectMapper->find($ids));
			}
			$this->_helper->cache->clean('toaster_301redirects', '301redirects');
		}
	}

	public function deeplinksAction() {
		$deeplinksForm    = new Application_Form_Deeplink();
		$pageMapper       = new Application_Model_Mappers_PageMapper();
		$deeplinksForm->setToasterPages($pageMapper->fetchIdUrlPairs());
		if($this->getRequest()->isPost()) {
			if($deeplinksForm->isValid($this->getRequest()->getParams())) {
				$data           = $deeplinksForm->getValues();
				$deeplinkMapper = new Application_Model_Mappers_DeeplinkMapper();
				$deeplink       = new Application_Model_Models_Deeplink();
				if(intval($data['url'])) {
					$deeplink->setType(Application_Model_Models_Deeplink::TYPE_INTERNAL);
					$page = $pageMapper->find($data['url']);
					$deeplink->setPageId($data['url']);
					$deeplink->setUrl($page->getUrl());
				}
				else {
					$deeplink->setType(Application_Model_Models_Deeplink::TYPE_EXTERNAL);
					$deeplink->setUrl($data['url']);
					$deeplink->setPageId(null);
				}
				$deeplink->setName($data['anchorText']);
				$deeplink->setBanned(false);
				$deeplink->setNofollow(false);
				$deeplink->registerObserver(new Tools_Seo_Watchdog());
				$deeplinkMapper->save($deeplink);
				$deeplink->notifyObservers();
				$this->_helper->response->success('Deeplink saved');
			}
		}
		else {

		}
		$this->view->form = $deeplinksForm;
	}

	public function removedeeplinkAction() {
		if($this->getRequest()->isPost()) {
			$ids = $this->getRequest()->getParam('id');
			if(is_array($ids)) {
				foreach ($ids as $id) {
					$this->_removeDeeplink($id);
				}
			}
			else {
				$this->_removeDeeplink($ids);
			}
		}
	}

	private function _removeDeeplink($deeplinkId) {
		$deeplinkMapper = new Application_Model_Mappers_DeeplinkMapper();
		$deeplink       = $deeplinkMapper->find($deeplinkId);

		$deeplink->registerObserver(new Tools_Deeplink_GarbageCollector(array(
			'action' => Tools_Deeplink_GarbageCollector::CLEAN_ONDELETE
		)));
		return $deeplinkMapper->delete($deeplink);
	}


	public function loaddeeplinkslistAction() {
		$deeplinkMapper = new Application_Model_Mappers_DeeplinkMapper();
		$this->view->deeplinks = array_reverse($deeplinkMapper->fetchAll(null, array('id')));
		$this->view->deeplinksList = $this->view->render('backend/seo/deeplinkslist.phtml');
	}
}

