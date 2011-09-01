<?php

class IndexController extends Zend_Controller_Action {

    public function init() {
		if($this->_helper->session->pluginRoutesFetched !== true) {
			Tools_Plugins_Tools::fetchPluginsRoutes();
			$this->_helper->session->pluginRoutesFetched = true;
		}
		$this->_helper->AjaxContext()->addActionContext('language', 'json')->initContext('json');
	}

    public function indexAction() {
		$page        = null;
		$pageContent = null;
		$currentUser = $this->_helper->session->getCurrentUser();
		$pageUrl     = $this->_helper->page->validate($this->getRequest()->getParam('page'));

		//Check if 301 redirect is present for requested page then do it
		$this->_helper->page->do301Redirect($pageUrl);


		// Loading page data using url from request. First checking cache, if no cache
		// loading from the database and save result to the cache
		if(null === ($page = $this->_helper->cache->load($pageUrl, 'pagedata_'))) {
			$page = Application_Model_Mappers_PageMapper::getInstance()->findByUrl($pageUrl);
			if(null !== $page) {
				$this->_helper->cache->save($pageUrl, $page, 'pagedata_');
			}
		}

		// If page doesn't exists in the system - show 404 page
		if(null === $page) {
			//@todo move to separate method
			//show 404 page and exit


			$page = Application_Model_Mappers_PageMapper::getInstance()->find404Page();

			if(!$page instanceof Application_Model_Models_Page) {
				$this->view->websiteUrl = $this->_helper->website->getUrl();
				$this->_helper->response->notFound($this->view->render('index/404page.phtml'));
				exit;
			}

			$this->getResponse()->setHeader('HTTP/1.1', '404 Not Found');
			$this->getResponse()->setHeader('Status', '404 File not found');

		}
		// Check if current user is allowed to see the requested page
		// (such as protected pages for members only)
		if(Tools_Security_Acl::isAllowed($page, $currentUser)) {

			//Check if page caching is allowed for current user
			if(Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_CACHE_PAGE, $currentUser)) {
				$pageContent = $this->_helper->cache->load($pageUrl, 'page_');
			}

			//Parsing page content and saving it to the cache
			if(null === $pageContent) {
				$themeData = Zend_Registry::get('theme');
				$parserOptions = array(
					'websiteUrl'   => $this->_helper->website->getUrl(),
					'websitePath'  => $this->_helper->website->getPath(),
					'currentTheme' => $this->_helper->config->getConfig('currentTheme'),
					'themePath'    => $themeData['path'],
				);
				$parser = new Tools_Content_Parser($page->getContent(), $page->toArray(), $parserOptions);
				$pageContent = $parser->parse();
				unset($parser);
				unset($themeData);
				$this->_helper->cache->save($page->getUrl(), $pageContent, 'page_');
			}
		}
		else {
			//if requested page is not allowed - redirect to the website index page
			$this->_helper->redirector->gotoUrl($this->_helper->website->getUrl());
		}

		// Finilize page generation routine
		$this->_complete($pageContent, $page->toArray());
	}


	private function _complete($pageContent, $pageData) {
		$head = '';
		$body = '';
		preg_match('~<head>(.*)</head>~sUui', $pageContent, $head);
		preg_match('~<body.*>(.*)</body>~usUi', $pageContent, $body);
		$this->view->head            = $head[1];
		$this->view->websiteUrl      = $this->_helper->website->getUrl();
		$this->view->websiteMainPage = $this->_helper->website->getDefaultPage();
		$this->view->currentTheme = $this->_helper->config->getConfig('currentTheme');
		if(Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_ADMINPANEL)) {
			unset($pageData['content']);
			$this->view->pageData = $pageData;
			$body[1] = $this->_helper->admin->renderAdminPanel($this->_helper->session->getCurrentUser()->getRoleId()) . $body[1];
		}
		$this->view->content = $body[1];
	}

	public function languageAction() {
		if($this->getRequest()->isPost()) {
			$language = substr($this->getRequest()->getParam('lng'), 0, 2);
			if($language) {
				$locale   = $this->_helper->session->locale;
				$locale->setLocale($locale->getLocaleToTerritory($language));
				$this->_helper->session->locale = $locale;
			}
		}
	}
}

