<?php

class IndexController extends Zend_Controller_Action {

    public function init() {
		$this->_helper->AjaxContext()->addActionContext('language', 'json')->initContext('json');
	}

    public function indexAction() {
		$page        = null;
		$pageContent = null;
		$currentUser = $this->_helper->session->getCurrentUser();

		// If page was queried as news page (in news context)
		$newsContext = $this->getRequest()->getParam('context', null);
		$newsContext = ($newsContext && $newsContext == Application_Model_Models_Page::CONTEXT_NEWS) ? true : false;

		// Geting requested url
		$pageUrl     = $this->getRequest()->getParam('page');

		// Trying to do canonic redirects
		$this->_helper->page->doCanonicalRedirect($pageUrl);

		// Preparing page url and make it valid
		$pageUrl     = $this->_helper->page->validate($pageUrl);

		//Check if 301 redirect is present for requested page then do it
		$this->_helper->page->do301Redirect($pageUrl);

		// Loading page data using url from request. First checking cache, if no cache
		// loading from the database and save result to the cache
		$pageCacheKey = (($newsContext) ? $pageUrl . 'newspage' : $pageUrl);
		if(null === ($page = $this->_helper->cache->load($pageCacheKey, 'pagedata_'))) {
			// Depends on what kind page it is (news or regular) get a needed mapper
			$mapper = ($newsContext) ? Application_Model_Mappers_NewsMapper::getInstance() : Application_Model_Mappers_PageMapper::getInstance();
			$page   = $mapper->findByUrl($pageUrl);
			if(null !== $page) {
				$this->_helper->cache->save($pageCacheKey, $page, 'pagedata_');
			}
		}

		// If page doesn't exists in the system - show 404 page
		if(null === $page) {
			//@todo move to separate method
			//show 404 page and exit

			$page = Application_Model_Mappers_PageMapper::getInstance()->find404Page();

			if(!$page instanceof Application_Model_Models_Page) {
				$this->view->websiteUrl = $this->_helper->website->getUrl();
				$this->view->adminPanel = $this->_helper->admin->renderAdminPanel($this->_helper->session->getCurrentUser()->getRoleId());
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
				$parser      = new Tools_Content_Parser($page->getContent(), $page->toArray(), $parserOptions);
				$pageContent = $parser->parse();
				unset($parser);
				unset($themeData);
				$this->_helper->cache->save($page->getUrl(), $pageContent, 'page_');
			}
		}
		else {
			//if requested page is not allowed - redirect to the signup landing page
			$signupLanding = Tools_Page_Tools::getLandingPage(Application_Model_Models_Page::OPT_SIGNUPLAND);
			$this->_helper->redirector->gotoUrl(($signupLanding instanceof Application_Model_Models_Page) ? $this->_helper->website->getUrl() . $signupLanding->getUrl() : $this->_helper->website->getUrl());
		}

		if(!$newsContext) {
			//sculpting check
			$pageContent = $this->_pageRunkSculptingDemand($page, $pageContent);
		}

		// Finilize page generation routine
		$this->_complete($pageContent, $page->toArray(), $newsContext);
	}


	private function _complete($pageContent, $pageData, $newsPage = false) {
		$head = '';
		$body = '';
		preg_match('~<head>(.*)</head>~sUui', $pageContent, $head);
		preg_match('~(<body.*>)(.*)</body>~usi', $pageContent, $body);
		$this->view->head            = $head[1];
		$this->view->websiteUrl      = $this->_helper->website->getUrl();
		$this->view->websiteMainPage = $this->_helper->website->getDefaultPage();
		$this->view->currentTheme    = $this->_helper->config->getConfig('currentTheme');
		$this->view->newsPage        = $newsPage;
		if(Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_ADMINPANEL)) {
			unset($pageData['content']);
			$this->view->pageData = $pageData;
			$body[1] .= $this->_helper->admin->renderAdminPanel($this->_helper->session->getCurrentUser()->getRoleId());
		}
		$this->view->pageData = $pageData;
		$this->view->bodyTag  = $body[1];
		$this->view->content  = $body[2];
	}

	private function _pageRunkSculptingDemand($page, $pageContent) {
		// run pr sculpting only for the not logged users
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_ADMINPANEL)) {
			//Cehcking if page has silo?
			if($page->getSiloId()) {
				$pageContent = Tools_Seo_Tools::runPageRankSculpting($page->getSiloId(), $pageContent);
				$this->view->sculptingReplacement = Zend_Registry::get('sculptingReplacement');
			}
		}
		return $pageContent;
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