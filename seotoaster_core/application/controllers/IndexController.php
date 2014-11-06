<?php
class IndexController extends Zend_Controller_Action {

    protected $_config = null;

    public function init() {
		$this->_helper->ajaxContext->addActionContext('language', 'json')->initContext('json');
        $this->_config = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
    }

    public function indexAction() {
		$page          = null;
		$pageContent   = null;

        $currentUser = $this->_helper->session->getCurrentUser();

	    // tracking referer
	    if (!isset($this->_helper->session->refererUrl)){
		    $refererUrl = $this->getRequest()->getHeader('referer');
		    $currentUser->setReferer($refererUrl);
		    $this->_helper->session->setCurrentUser($currentUser);
		    $this->_helper->session->refererUrl = $refererUrl;
	    }

		// Getting requested url. If url is not specified - get index.html
		$pageUrl = filter_var($this->getRequest()->getParam('page', Helpers_Action_Website::DEFAULT_PAGE), FILTER_SANITIZE_STRING);

		// Trying to do canonical redirects
		$this->_helper->page->doCanonicalRedirect($pageUrl);

		//Check if 301 redirect is present for requested page then do it
		$this->_helper->page->do301Redirect($pageUrl);

		// Loading page data using url from request. First checking cache, if no cache
		// loading from the database and save result to the cache
		$pageCacheKey = md5($pageUrl);
        if(Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_CACHE_PAGE)) {
            $page = $this->_helper->cache->load($pageCacheKey, 'pagedata_');
        }

        // page is not in cache
        if($page === null) {
            $page = Application_Model_Mappers_PageMapper::getInstance()->findByUrl($pageUrl);
        }

        // page found
        if($page instanceof Application_Model_Models_Page) {
            $cacheTag = preg_replace('/[^\w\d_]/', '', $page->getTemplateId());
            $this->_helper->cache->save($pageCacheKey, $page, 'pagedata_', array($cacheTag, 'pageid_' . $page->getId()));
            $tpl  =  Application_Model_Mappers_TemplateMapper::getInstance()->find($page->getTemplateId());
            $this->view->tplType = $tpl->getType();
        }

		// If page doesn't exists in the system - show 404 page
		if($page === null) {
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

        //if requested page is not allowed - redirect to the signup landing page
        if (!Tools_Security_Acl::isAllowed($page)) {
            $signupLanding = Tools_Page_Tools::getLandingPage(Application_Model_Models_Page::OPT_SIGNUPLAND);
            $this->_helper->redirector->gotoUrl(($signupLanding instanceof Application_Model_Models_Page) ? $this->_helper->website->getUrl() . $signupLanding->getUrl() : $this->_helper->website->getUrl());
        }

        // Mobile switch
        if ((bool) $this->_config->getConfig('enableMobileTemplates')) {
            if ($this->_request->isGet() && $this->_request->has('mobileSwitch')) {
                $showMobile = filter_var($this->_request->getParam('mobileSwitch'), FILTER_SANITIZE_NUMBER_INT);
                if (!is_null($showMobile)) {
                    // save mobileSwitch in session
                    $this->_helper->session->mobileSwitch = (bool) $showMobile;
                    // redirect to target page
                    $this->redirect($this->_helper->website->getUrl().$page->getUrl());
                }
            }

            if (!isset($showMobile) && isset($this->_helper->session->mobileSwitch)) {
                $showMobile = $this->_helper->session->mobileSwitch;
            } else {
                $showMobile = $this->_helper->mobile->isMobile();
            }

            // Mobile detect
            if ($showMobile === true) {
                $mobileTemplate = Application_Model_Mappers_TemplateMapper::getInstance()->find('mobile_'.$page->getTemplateId());
                if (null !== ($mobileTemplate)) {
                    $page->setTemplateId($mobileTemplate->getName())->setContent($mobileTemplate->getContent());
                }
                unset($mobileTemplate);
            }
        }

        $pageData = $page->toArray();

        // Parsing page content and saving it to the cache
        if ($pageContent === null) {
            $themeData     = Zend_Registry::get('theme');
            $websitePath   = $this->_helper->website->getPath();
            $currentTheme  = $this->_config->getConfig('currentTheme');
            $parserOptions = array(
                'websiteUrl'   => $this->_helper->website->getUrl(),
                'websitePath'  => $websitePath,
                'currentTheme' => $currentTheme,
                'themePath'    => Tools_Filesystem_Tools::cleanWinPath($themeData['path']),
            );

            // if developerMode = 1, parsing template directly from files
            if ((bool) $this->_config->getConfig('enableDeveloperMode')) {
                $filePath = $websitePath.$themeData['path'].$currentTheme.DIRECTORY_SEPARATOR.$page->getTemplateId()
                    .'.html';
                if (file_exists($filePath)) {
                    $page->setContent(Tools_Filesystem_Tools::getFile($filePath));
                }
            }

            /**
             * Load toaster parser closure from the registry
             * @var Closure $parser
             */
            $parser      = Zend_Registry::get('Toaster_Parser');
            $pageContent = $parser($page->getContent(), $pageData, $parserOptions)->parse();

            unset($parser, $themeData);
        }

    	$pageContent = $this->_pageRunkSculptingDemand($page, $pageContent);

		// Finalize page generation routine
		$this->_complete($pageContent, $pageData, $parserOptions);
	}


	private function _complete($pageContent, $pageData, $parserOptions) {
		$head    = '';
		$body    = '';

		//parsing seo data
		$seoData = Tools_Seo_Tools::loadSeodata();
		$seoData = $seoData->toArray();
		unset($seoData['id']);
		$seoData = array_map(function($item) use ($pageData, $parserOptions){
			$parser = new Tools_Content_Parser(null, $pageData, $parserOptions);
			return !empty($item) ? $parser->setContent($item)->parseSimple() : $item;
		}, $seoData);

		preg_match('~(<body[^\>]*>)(.*)</body>~usi', $pageContent, $body);

        // setting default charset
        if ($this->view->doctype()->isHtml5()) {
            $this->view->headMeta()->setCharset('utf-8');
        }
		$this->_extendHead($pageContent);

		$this->view->placeholder('seo')->exchangeArray($seoData);

		$this->view->websiteUrl      = $parserOptions['websiteUrl'];
        $this->view->websiteMainPage = Helpers_Action_Website::DEFAULT_PAGE;
		$this->view->currentTheme    = $parserOptions['currentTheme'];

		// building canonical url
		if ('' === ($canonicalScheme = $this->_config->getConfig('canonicalScheme'))){
			$canonicalScheme = $this->getRequest()->getScheme();
		}

        // Is news-index page
        if (!empty($pageData['extraOptions'])
            && in_array('newslog', Tools_Plugins_Tools::getEnabledPlugins(true))
            && in_array(Newslog::OPTION_PAGE_INDEX, $pageData['extraOptions'])
        ) {
            $url = Newslog_Models_Mapper_ConfigurationMapper::getInstance()->fetchConfigParam('folder');
            $url = trim($url, '/').'/';
        }
        else {
            $url = ($pageData['url'] !== Helpers_Action_Website::DEFAULT_PAGE) ? $pageData['url'] : '';
        }
        $this->view->canonicalUrl = $canonicalScheme.'://'.parse_url($parserOptions['websiteUrl'], PHP_URL_HOST)
            .parse_url($parserOptions['websiteUrl'], PHP_URL_PATH).$url;

        $this->view->pageData     = $pageData;
        if(Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_ADMINPANEL)) {
			unset($pageData['content']);
			$body[1] .= $this->_helper->admin->renderAdminPanel($this->_helper->session->getCurrentUser()->getRoleId());
		}
		$this->view->bodyTag  = $body[1];
		$this->view->content  = $body[2];
        $locale               = Zend_Locale::getLocaleToTerritory($this->_config->getConfig('language'));
        $this->view->htmlLang = substr($locale, 0, strpos($locale, '_'));
        $this->view->minify   = $this->_config->getConfig('enableMinify') && !Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_LAYOUT);
	}

	private function _extendHead($pageContent) {
		preg_match('~<head>.*</head>~sUui', $pageContent, $head);

		if (empty($head)){
			return;
		}

        $head[0] = preg_replace_callback("~(<meta[^>]+name=\"(?:description|keywords)\"[^>]" . "+content=\")(.*)(\"[^>]+>)~i",
            function($matches) {
                return $matches[1] . htmlspecialchars(str_replace('"', '', $matches[2]), ENT_COMPAT, 'UTF-8') . $matches[3];
            }  ,$head[0]);

		$dom = new DOMDocument();
		@$dom->loadHTML($head[0]);

		foreach ($dom->getElementsByTagName('head') as $head){
			foreach ($head->childNodes as $node ) {
				$name = preg_replace('~[^\w\d]*~','',$node->nodeName);
				switch ($name) {
					case 'meta':
						$attributes = array();
						foreach($node->attributes as $attr){
							$attributes[$attr->name] = $attr->value;
							unset($attr);
						}
						if (isset($attributes['name'])){
							$this->view->headMeta()->appendName($attributes['name'], $attributes['content']);
						} elseif (isset($attributes['http-equiv'])){
							$this->view->headMeta()->appendHttpEquiv($attributes['http-equiv'], $attributes['content']);
						} else {
							if ($this->view->doctype()->isRdfa() && isset($attributes['property'])){
								$this->view->headMeta()->setProperty($attributes['property'], $attributes['content']);
							} elseif ($this->view->doctype()->isHtml5() && isset($attributes['charset'])){
								$this->view->headMeta()->setCharset($attributes['charset']);
							} else {
								$this->view->placeholder('misc')->set($this->view->placeholder('misc').PHP_EOL.$dom->saveXML($node));
							}
						}
						unset($attributes);
						break;
					case 'title':
						$this->view->headTitle($node->nodeValue);
						break;
					case 'script':
						$attributes = array();
						foreach($node->attributes as $attr){
							$attributes[$attr->name] = $attr->value;
							unset($attr);
						}
						if (isset($attributes['type'])){
							$type = $attributes['type'];
							unset($attributes['type']);
						} else {
							$type = 'text/javascript';
						}
						if ($node->hasAttribute('src')){
							$this->view->headScript()->appendFile($node->getAttribute('src'), $type, $attributes);
						} else {
							if ($type !== 'text/javascript'){
								$this->view->placeholder('misc')->set($this->view->placeholder('misc').PHP_EOL.$dom->saveXML($node));
							} else {
								$this->view->headScript()->appendScript($node->nodeValue, $type, $attributes);
							}
						}
						break;
					case 'link':
						if (strtolower($node->getAttribute('rel')) === 'stylesheet' ){
							$this->view->headLink()->appendStylesheet(
								$node->getAttribute('href'),
								$node->getAttribute('media')
							);
							break;
						}
					default:
						$this->view->placeholder('misc')->set($this->view->placeholder('misc').PHP_EOL.$dom->saveXML($node));
						break;
				}
			}
		}
		return;
	}

	private function _pageRunkSculptingDemand($page, $pageContent) {
		// run pr sculpting only for the not logged users
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_ADMINPANEL)) {
			//Checking if page has silo?
			if($page->getSiloId()) {
				$pageContent = Tools_Seo_Tools::runPageRankSculpting($page->getSiloId(), $pageContent);
				$this->view->sculptingReplacement = Zend_Registry::get('sculptingReplacement');
			}
		}
		return $pageContent;
	}

    /**
     * Language changing route:
     * via GET - /language/lng/%locale%
     * via POST - /language/ with data {lng: %locale%}
     */
	public function languageAction() {
        $this->_helper->layout->disableLayout(true);
        $this->_helper->viewRenderer->setNoRender(true);

        $language = filter_var($this->getRequest()->getParam('lng'),FILTER_SANITIZE_STRING);
        $originUrl = parse_url($this->_helper->website->getUrl(), PHP_URL_HOST);

        if(!empty($language) ) {
            $result = $this->_helper->language->setLanguage($language);

            $referer = $this->getRequest()->getServer('HTTP_REFERER');

            if (!$this->getRequest()->isXmlHttpRequest()){

                if ( !empty($referer) && parse_url($referer, PHP_URL_HOST) === $originUrl ) {
                    $this->redirect($this->getRequest()->getServer('HTTP_REFERER'));
                }
            } else {
                $this->view->result = $result;
            }
        }
	}
}
