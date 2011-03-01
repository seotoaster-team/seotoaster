<?php

class IndexController extends Zend_Controller_Action {

	protected $_websiteData = '';
	protected $_acl         = null;

    public function init() {
		$this->_websiteData = Zend_Registry::get('website');
		$this->_acl         = Zend_Registry::get('acl');
	}

    public function indexAction() {
		$pageUrl     = $this->_helper->page->validateRequestedPage($this->getRequest()->getParam('page'));
		$page        = null;
		$pageContent = null;
		if(null === ($page = $this->_helper->cache->load($pageUrl, 'pagedata_'))) {
			$pageMapper = new Application_Model_Mappers_PageMapper();
			$page = $pageMapper->findByUrl($pageUrl);
			if(null !== $page) {
				$this->_helper->cache->save($pageUrl, $page, 'pagedata_');
			}
		}
		if(null === $page) {
			//show 404 page and exit
			Zend_Debug::dump('404 page'); die();
		}
		if($this->_acl->isAllowed($this->_helper->session->getCurrentUser(), $page)) {
			if($this->_acl->isAllowed($this->_helper->session->getCurrentUser(), Tools_Security_Acl::RESOURCE_CACHE_PAGE)) {
				$pageContent = $this->_helper->cache->load($pageUrl, 'page_');
			}
			if(null === $pageContent) {
				$themeData = Zend_Registry::get('theme');
				$parserOptions = array(
					'websiteUrl'   => $this->_websiteData['url'],
					'websitePath'  => $this->_websiteData['path'],
					'currentTheme' => $this->_helper->config->getConfig('current_theme'),
					'themePath'    => $themeData['path'],
				);
				$parser = new Tools_Content_Parser($page->getContent(), $page->toArray(), $parserOptions);
				$pageContent = $parser->parse();
				$this->_helper->cache->save($page->getUrl(), $pageContent, 'page_');
			}
		}
		else {
			$this->_redirect($this->_websiteData['url']);
		}
		$this->_complete($pageContent);
	}


	private function _complete($pageContent) {
		$head = '';
		$body = '';
		preg_match('~<head>(.*)</head>~sUi', $pageContent, $head);
		preg_match('~<body>(.*)</body>~sUi', $pageContent, $body);
		$this->view->head     = $head[1];
		$this->view->acl      = $this->_acl;
		$userRole             = $this->_helper->session->getCurrentUser()->getRoleId();
		$this->view->userRole = $userRole;
		if($this->_acl->isAllowed($this->_helper->session->getCurrentUser(), Tools_Security_Acl::RESOURCE_ADMINPANEL)) {
			$body[1] = $this->_helper->admin->renderAdminPanel($userRole) . $body[1];
		}
		$this->view->content      = $body[1];
		$this->view->websiteUrl   = $this->_websiteData['url'];
		$this->view->currentTheme = $this->_helper->config->getConfig('current_theme');
	}
}

