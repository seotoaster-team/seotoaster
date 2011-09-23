<?php

/**
 * SeoController
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Backend_SeoController extends Zend_Controller_Action {

	private $_translator = null;

	public function init() {
		parent::init();
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_PAGES)) {
			$this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
		}
		$this->_helper->AjaxContext()->addActionContexts(array(
			'loaddeeplinkslist'	=> 'json',
			'loadredirectslist' => 'json',
			'removeredirect'    => 'json',
			'removedeeplink'    => 'json',
			'loadsculptingdata' => 'json',
			'addsilotopage'     => 'json',
			'silocat'           => 'json',
			'unsilocat'         => 'json'
			))->initContext('json');
		$this->_translator      = Zend_Registry::get('Zend_Translate');
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
		$pageMapper     = Application_Model_Mappers_PageMapper::getInstance();
		$redirectMapper = Application_Model_Mappers_RedirectMapper::getInstance();

		$redirectForm->setToasterPages($pageMapper->fetchIdUrlPairs());
		$redirectForm->setDefault('fromUrl', 'http://');

		if(!$this->getRequest()->isPost()) {
			$this->view->redirects = $redirectMapper->fetchRedirectMap();
		}
		else {
			if($redirectForm->isValid($this->getRequest()->getParams())) {
				$data          = $redirectForm->getValues();
				$redirect      = new Application_Model_Models_Redirect();
				$fromUrlPath   = Tools_System_Tools::getUrlPath($data['fromUrl']);
				$inDbValidator = new Zend_Validate_Db_NoRecordExists(array(
					'table' => 'redirect',
					'field' => 'from_url'
				));
				if(!$inDbValidator->isValid($fromUrlPath)) {
					$this->_helper->response->fail(implode('<br />', $inDbValidator->getMessages()));
					exit;
				}
				$redirect->setFromUrl(Tools_System_Tools::getUrlPath($data['fromUrl']));
				$redirect->setDomainFrom(Tools_System_Tools::getUrlScheme($data['fromUrl']) . '://' . Tools_System_Tools::getUrlHost($data['fromUrl']) . '/');
				if(intval($data['toUrl'])) {
					$page = $pageMapper->find($data['toUrl']);
					$redirect->setDomainTo($this->_helper->website->getUrl());
					$redirect->setToUrl($page->getUrl());
					$redirect->setPageId($page->getId());
				}
				else {
					$urlValidator = new Validators_UrlRegex();
					if(!$urlValidator->isValid($data['toUrl'])) {
						$this->_helper->response->fail('External url <br />' . implode('<br />', $urlValidator->getMessages()));
						exit;
					}
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
		$redirects      = Application_Model_Mappers_RedirectMapper::getInstance()->fetchAll(null, array('id'));
		$this->view->redirects = array_reverse($redirects);
		$this->view->redirectsList = $this->view->render('backend/seo/loadredirectslist.phtml');
	}

	public function removeredirectAction() {
		if($this->getRequest()->isPost()) {
			$ids            = $this->getRequest()->getParam('id');
			$redirectMapper = Application_Model_Mappers_RedirectMapper::getInstance();
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
		$pageMapper       = Application_Model_Mappers_PageMapper::getInstance();
		$deeplinksForm->setToasterPages($pageMapper->fetchIdUrlPairs());
		if($this->getRequest()->isPost()) {
			if($deeplinksForm->isValid($this->getRequest()->getParams())) {
				$data           = $deeplinksForm->getValues();
				$deeplink       = new Application_Model_Models_Deeplink();
				if(intval($data['url'])) {
					$deeplink->setType(Application_Model_Models_Deeplink::TYPE_INTERNAL);
					$page = $pageMapper->find($data['url']);
					$deeplink->setPageId($data['url']);
					$deeplink->setUrl($page->getUrl());
				}
				else {
					$deeplink->setType(Application_Model_Models_Deeplink::TYPE_EXTERNAL);
					$urlValidator = new Validators_UrlRegex();
					if(!$urlValidator->isValid($data['url'])) {
						$this->_helper->response->fail('External url <br />' . implode('<br />', $urlValidator->getMessages()));
						exit;
					}
					$deeplink->setUrl($data['url']);
					$deeplink->setPageId(null);
				}
				$deeplink->setName($data['anchorText']);
				$deeplink->setBanned(false);
				$deeplink->setNofollow($data['nofollow']);
				$deeplink->registerObserver(new Tools_Seo_Watchdog());
				Application_Model_Mappers_DeeplinkMapper::getInstance()->save($deeplink);
				$deeplink->notifyObservers();
				$this->_helper->response->success('Deeplink saved');
			}
			else {
				$this->_helper->response->fail(Tools_Content_Tools::proccessFormMessagesIntoHtml($deeplinksForm->getMessages(), get_class($deeplinksForm)));
				exit;
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
		$deeplinkMapper = Application_Model_Mappers_DeeplinkMapper::getInstance();
		$deeplink       = $deeplinkMapper->find($deeplinkId);

		$deeplink->registerObserver(new Tools_Deeplink_GarbageCollector(array(
			'action' => Tools_Deeplink_GarbageCollector::CLEAN_ONDELETE
		)));
		return $deeplinkMapper->delete($deeplink);
	}


	public function loaddeeplinkslistAction() {
		$this->view->deeplinks = array_reverse(Application_Model_Mappers_DeeplinkMapper::getInstance()->fetchAll(null, array('id')));
		$this->view->deeplinksList = $this->view->render('backend/seo/deeplinkslist.phtml');
	}


	public function sculptingAction() {
		$siloForm = new Application_Form_Silo();
		if($this->getRequest()->isPost()) {
			if($siloForm->isValid($this->getRequest()->getParams())) {
				$silo = new Application_Model_Models_Silo($siloForm->getValues());
				if(Application_Model_Mappers_SiloMapper::getInstance()->save($silo)) {
					$this->_helper->response->success('Silo added.');
				}
			}
		}
		$this->view->siloForm = $siloForm;
	}

	public function loadsculptingdataAction() {
		$tree  = array();

		$pages = Application_Model_Mappers_PageMapper::getInstance()->fetchAll();
		foreach ($pages as $key => $page) {
			if($page->getParentId() == 0) {
				$silo = Application_Model_Mappers_SiloMapper::getInstance()->find($page->getSiloId());
				if(!$silo instanceof Application_Model_Models_Silo) {
					$siloCat = false;
				}
				else {
					$siloCat = ($silo->getName() == $page->getNavName()) ? true : false;
				}

				$tree[$page->getId()] = array(
					'label'    => $page->getNavName(),
					'isSilo'   => $siloCat,
					//'category' => $page,

				);
				$tree[$page->getId()]['subpages'][] = $page;
				foreach ($pages as $subPage) {
					if($subPage->getParentId() == $page->getId()) {
						$tree[$page->getId()]['subpages'][] = $subPage;
					}
				}
			}
			else {
				if(!isset($tree[-1])) {
					$tree[-1] = array(
						'label'    => $this->_translator->translate('Without category'),
						'category' => ''
					);
				}
				$tree[-1]['subpages'][] = $page;
			}
		}

		$silos        = Application_Model_Mappers_SiloMapper::getInstance()->fetchAll();
		$silosOptions = array(0 => 'select a silo');

		if(!empty ($silos)) {
			foreach ($silos as $silo) {
				$silosOptions[$silo->getId()] = $silo->getName();
			}
		}

		$this->view->silosOptions  = $silosOptions;
		$this->view->pages         = $tree;
		$this->view->sculptingList = $this->view->render('backend/seo/sculptinglist.phtml');
	}

	public function addsilotopageAction() {
		if($this->getRequest()->isPost()) {
			$page = Application_Model_Mappers_PageMapper::getInstance()->find(intval($this->getRequest()->getParam('pid')));
			if($page instanceof Application_Model_Models_Page) {
				$page->setSiloId(intval($this->getRequest()->getParam('sid', 0)));
				Application_Model_Mappers_PageMapper::getInstance()->save($page);
			}
		}
	}

	public function silocatAction() {
		if($this->getRequest()->isPost()) {
			$catPage = Application_Model_Mappers_PageMapper::getInstance()->find(intval($this->getRequest()->getParam('cid')));
			$silo    = Application_Model_Mappers_SiloMapper::getInstance()->findByName($catPage->getNavName());
			$silo    = ($silo instanceof Application_Model_Models_Silo) ? $silo : new Application_Model_Models_Silo();
			$silo->setName($catPage->getNavName());
			$silo->setRelatedPages(array_merge(Application_Model_Mappers_PageMapper::getInstance()->findByParentId($catPage->getId()), array($catPage)));
			$siloId = Application_Model_Mappers_SiloMapper::getInstance()->save($silo);
		}
	}

	public function unsilocatAction() {
		if($this->getRequest()->isPost()) {
			$pageMapper = Application_Model_Mappers_PageMapper::getInstance();
			$catPage = $pageMapper->find(intval($this->getRequest()->getParam('cid')));
			if($catPage instanceof Application_Model_Models_Page) {
				$pages = array_merge($pageMapper->findByParentId($catPage->getId()), array($catPage));
				foreach ($pages as $page) {
					$page->setSiloId(0);
					$pageMapper->save($page);
				}
			}
		}
	}

}

