<?php

/**
 * SeoController
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Backend_SeoController extends Zend_Controller_Action {

	const SILOCAT_ADD    = 'add';

    const SILOCAT_REMOVE = 'remove';

	private $_translator           = null;

    public static $_allowedActions = array('sitemap', 'feeds');

	public function init() {
		parent::init();
        if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_PAGE_PUBLIC)) {
            $this->redirect($this->_helper->website->getUrl(), array('exit' => true));
        }
        if(!Tools_Security_Acl::isActionAllowed(Tools_Security_Acl::RESOURCE_SEO)) {
            $this->redirect($this->_helper->website->getUrl(), array('exit' => true));
        }
		$this->_helper->AjaxContext()->addActionContexts(array(
			'loaddeeplinkslist'	=> 'json',
			'loadredirectslist' => 'json',
			'removeredirect'    => 'json',
			'removedeeplink'    => 'json',
			'loadsculptingdata' => 'json',
			'addsilotopage'     => 'json',
			'silocat'           => 'json',
			'unsilocat'         => 'json',
			'managesilos'       => 'json'
		))->initContext('json');

        $this->_helper->contextSwitch()
            ->addActionContext('sitemap', 'xml')
            ->addActionContext('feeds', 'xml')
            ->initContext();

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
            $robotsForm = Tools_System_Tools::addTokenValidatorZendForm($robotsForm, Tools_System_Tools::ACTION_PREFIX_ROBOTS);
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
        $secureToken = Tools_System_Tools::initZendFormCsrfToken($robotsForm, Tools_System_Tools::ACTION_PREFIX_ROBOTS);
        $this->view->secureToken = $secureToken;
        $this->view->helpSection = 'robots';
		$this->view->form        = $robotsForm;
	}

	public function redirectsAction() {
		$redirectForm   = new Application_Form_Redirect();
		$pageMapper     = Application_Model_Mappers_PageMapper::getInstance();
		$redirectMapper = Application_Model_Mappers_RedirectMapper::getInstance();

		$redirectForm->setToasterPages($pageMapper->fetchIdUrlPairs());
		$redirectForm->setDefault('fromUrl', 'http://');

		if ($this->getRequest()->isPost()) {
            $redirectForm = Tools_System_Tools::addTokenValidatorZendForm($redirectForm, Tools_System_Tools::ACTION_PREFIX_REDIRECTS);
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
                $websiteUrl = $this->_helper->website->getUrl();
                $withSubfolder = Tools_System_Tools::getUrlPath($websiteUrl);
                if ($withSubfolder && preg_match('~'.preg_quote($websiteUrl, '/').'~', $data['fromUrl'])) {
                    $cleanUrl = trim(str_replace($websiteUrl, '', $data['fromUrl']), '/');
                    $redirect->setFromUrl($cleanUrl);
                    $redirect->setDomainFrom($websiteUrl);
                }else{
                    $redirect->setFromUrl(Tools_System_Tools::getUrlPath($data['fromUrl']));
                    $redirect->setDomainFrom(Tools_System_Tools::getUrlScheme($data['fromUrl']) . '://' . Tools_System_Tools::getUrlHost($data['fromUrl']) . '/');
                }

				if(intval($data['toUrl'])) {
					$page = $pageMapper->find($data['toUrl']);
					$redirect->setDomainTo($websiteUrl);
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
        $secureToken = Tools_System_Tools::initZendFormCsrfToken($redirectForm, Tools_System_Tools::ACTION_PREFIX_REDIRECTS);
        $this->view->secureToken = $secureToken;
        $this->view->helpSection = '301s';
		$this->view->form = $redirectForm;
	}

	public function loadredirectslistAction() {
		$redirects      = Application_Model_Mappers_RedirectMapper::getInstance()->fetchAll(null, array('id'));
		$this->view->redirects = array_reverse($redirects);
		$this->view->redirectsList = $this->view->render('backend/seo/loadredirectslist.phtml');
	}

	public function removeredirectAction() {
		if($this->getRequest()->isDelete()) {
			$ids            = explode(',', $this->getRequest()->getParam('id'));
			$redirectMapper = Application_Model_Mappers_RedirectMapper::getInstance();
			if(is_array($ids)) {
				foreach ($ids as $id) {
					$redirectMapper->delete($redirectMapper->find($id));
				}
			}
			$this->_helper->cache->clean('toaster_301redirects', '301redirects');
			$this->_helper->response->success($this->_helper->language->translate('Redirect(s) removed.'));
		}
	}

	public function deeplinksAction() {
		$deeplinksForm    = new Application_Form_Deeplink();
		$pageMapper       = Application_Model_Mappers_PageMapper::getInstance();
		$deeplinksForm->setToasterPages($pageMapper->fetchIdUrlPairs());
		if($this->getRequest()->isPost()) {
            $deeplinksForm = Tools_System_Tools::addTokenValidatorZendForm($deeplinksForm, Tools_System_Tools::ACTION_PREFIX_DEEPLINKS);
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
        $secureToken = Tools_System_Tools::initZendFormCsrfToken($deeplinksForm, Tools_System_Tools::ACTION_PREFIX_DEEPLINKS);
        $this->view->secureToken = $secureToken;
        $this->view->helpSection = 'deeplinks';
		$this->view->form        = $deeplinksForm;
	}

	public function removedeeplinkAction() {
		if($this->getRequest()->isDelete()) {
			$ids = explode(',', $this->getRequest()->getParam('id'));
			if(is_array($ids)) {
				foreach ($ids as $id) {
					$this->_removeDeeplink($id);
				}
			}
			$this->_helper->response->success($this->_helper->language->translate('Deeplink(s) removed.'));
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
		$this->view->deeplinks = Application_Model_Mappers_DeeplinkMapper::getInstance()->fetchAll(null, array('name'));
		$this->view->deeplinksList = $this->view->render('backend/seo/deeplinkslist.phtml');
	}


	public function sculptingAction() {
		$siloForm = new Application_Form_Silo();
		if($this->getRequest()->isPost()) {
            $siloForm = Tools_System_Tools::addTokenValidatorZendForm($siloForm, Tools_System_Tools::ACTION_PREFIX_SILOS);
            if($siloForm->isValid($this->getRequest()->getParams())) {
				$silo = new Application_Model_Models_Silo($siloForm->getValues());
				if(Application_Model_Mappers_SiloMapper::getInstance()->save($silo)) {
					$this->_helper->response->success('Silo added.');
				}
			}
			else {
				$this->_helper->response->fail(Tools_Content_Tools::proccessFormMessagesIntoHtml($siloForm->getMessages(), get_class($siloForm)));
			}
		}
        $secureToken = Tools_System_Tools::initZendFormCsrfToken($siloForm, Tools_System_Tools::ACTION_PREFIX_SILOS);
        $this->view->secureToken = $secureToken;
        $this->view->helpSection = 'sculpting';
		$this->view->siloForm    = $siloForm;
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
					$silo     = Application_Model_Mappers_SiloMapper::getInstance()->findByName($this->_translator->translate('Without category'));
					$tree[-1] = array(
						'label'    => $this->_translator->translate('Without category'),
						'category' => '',
						'isSilo'   => ($silo instanceof Application_Model_Models_Silo && $page->getSiloId() == -1)
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
		ksort($tree);
		$this->view->pages         = array_reverse($tree, true);
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
			$action = $this->getRequest()->getParam('act', false);
			if(!$action) {
				throw new Exceptions_SeotoasterException($this->_translator->translate('Action is not defined'));
			}
			$pageMapper       = Application_Model_Mappers_PageMapper::getInstance();
			$cid              = intval($this->getRequest()->getParam('cid'));
			$categoryPage     = ($cid != Application_Model_Models_Page::IDCATEGORY_DEFAULT) ? $pageMapper->find($cid) : $cid;
            $siloRelatedPages = $pageMapper->findByParentId(
                    ($categoryPage instanceof Application_Model_Models_Page) ? $categoryPage->getId() : $categoryPage
            );
			if($categoryPage === null) {
				throw new Exceptions_SeotoasterException($this->_translator->translate('Cannot load category page'));
			}
            $tokenToValidate = $this->getRequest()->getParam(Tools_System_Tools::CSRF_SECURE_TOKEN, false);
            $valid = Tools_System_Tools::validateToken($tokenToValidate, Tools_System_Tools::ACTION_PREFIX_SILOS);
            if (!$valid) {
                exit;
            }
			switch ($action) {
				case self::SILOCAT_ADD:
					$siloMapper       = Application_Model_Mappers_SiloMapper::getInstance();
					$silo             = $siloMapper->findByName(($categoryPage instanceof Application_Model_Models_Page) ? $categoryPage->getNavName() : $this->_helper->language->translate('Without category'));
					$silo             = ($silo instanceof Application_Model_Models_Silo) ? $silo : new Application_Model_Models_Silo();
					if($categoryPage instanceof Application_Model_Models_Page) {
                        $relatedPages = array($categoryPage);
                        if (is_array($siloRelatedPages) && !empty($siloRelatedPages)) {
                            $relatedPages = array_merge($siloRelatedPages, $relatedPages);
                        }
						$silo->setName($categoryPage->getNavName())
							->setRelatedPages($relatedPages);
                        unset($relatedPages);
					}
					else {
						$silo->setName($this->_helper->language->translate('Without category'));
                        if (!empty($siloRelatedPages)) {
                            $silo->setRelatedPages($siloRelatedPages);
                        }
					}
					$siloId = $siloMapper->save($silo);
					if($siloId) {
						$this->_helper->response->success('Silo added');
					}
				break;
				case self::SILOCAT_REMOVE:
					if($categoryPage instanceof Application_Model_Models_Page) {
						$pageMapper->save($categoryPage->setSiloId(0));
					}
					if(!empty ($siloRelatedPages)) {
						foreach ($siloRelatedPages as $page) {
							$page->setSiloId(0);
							$pageMapper->save($page);
						}
					}
				break;
			}
		}
	}

	public function managesilosAction() {
        $siloForm = new Application_Form_Silo();
        if($this->getRequest()->isGet()) {
			$this->view->siloForm = $siloForm;
		}
		else {
			$action = $this->getRequest()->getParam('act', null);
			if($action === null) {
				$this->_helper->response->fail($this->_helper->language->translate('Action is not defined'));
			}
			switch ($action) {
				case 'loadlist':
					$this->view->silos = Application_Model_Mappers_SiloMapper::getInstance()->fetchAll(null, array('name'));
					$this->_helper->response->success($this->view->render('backend/seo/siloslist.phtml'));
				break;
				case 'remove':
					if($this->_request->isDelete()){
                        $ids = explode(',', $this->getRequest()->getParam('id'));
                        if(empty ($ids)) {
                            $this->_helper->response->fail($this->_helper->language->translate('Silo id is not specified'));
                        }
                        $siloMapper = Application_Model_Mappers_SiloMapper::getInstance();
                        foreach ($ids as $siloId) {
                            $silo = $siloMapper->find($siloId, true);
                            if(!$silo instanceof Application_Model_Models_Silo) {
                                $this->_helper->response->fail($this->_helper->language->translate('Cannot find silo to remove.'));
                            }
                            $silo->registerObserver(new Tools_Seo_GarbageCollector(array(
                                'action' => Tools_Seo_GarbageCollector::CLEAN_ONDELETE
                            )));
                            $siloMapper->delete($silo);
                        }
                        $this->_helper->response->success($this->_helper->language->translate('Silo(s) removed.'));
                    }
				break;
			}
		}
        $secureToken = Tools_System_Tools::initZendFormCsrfToken($siloForm, Tools_System_Tools::ACTION_PREFIX_SILOS);
        $this->view->secureToken = $secureToken;
	}

    /**
     * Serve sitemaps
     *
     */
    public function sitemapAction() {
        //disable renderer
        $this->_helper->viewRenderer->setNoRender(true);

        //get sitemap type from the params
        if(($sitemapType = $this->getRequest()->getParam('type', '')) == Tools_Content_Feed::SMFEED_TYPE_REGULAR) {
            //regular sitemap.xml requested
            if(null === ($this->view->pages = $this->_helper->cache->load('sitemappages', 'sitemaps_'))) {
                if (in_array('newslog', Tools_Plugins_Tools::getEnabledPlugins(true))) {
                    $this->view->newsPageUrlPath = Newslog_Models_Mapper_ConfigurationMapper::getInstance()->fetchConfigParam('folder');
                }
                $pageMapper = Application_Model_Mappers_PageMapper::getInstance();
                $where = $pageMapper->getDbTable()->getAdapter()->quoteInto('external_link_status <> ?', '1');
                $pages = Application_Model_Mappers_PageMapper::getInstance()->fetchAll($where);
                if(is_array($pages) && !empty($pages)) {

                    $quoteInstalled = Tools_Plugins_Tools::findPluginByName('quote')->getStatus() == Application_Model_Models_Plugin::ENABLED;
                    $pages = array_filter($pages, function($page) use($quoteInstalled) {
                        if($page->getExtraOption(Application_Model_Models_Page::OPT_PROTECTED) ||
                                                 $page->getDraft() ||
                                                 $page->getIs404page() ||
                                                 ($quoteInstalled && (intval($page->getParentId()) === Quote::QUOTE_CATEGORY_ID))) {
                            return false;
                        }
                        return true;
                    });

                } else {
                    $pages = array();
                }
                $this->view->pages = $pages;
                $this->_helper->cache->save('sitemappages', $this->view->pages, 'sitemaps_', array('sitemaps'));
            }

        } else if($sitemapType == Tools_Content_Feed::SMFEED_TYPE_INDEX) {
            //default sitemaps
            $sitemaps = array(
                'sitemap' => array(
                    'extension' => 'xml',
                    'lastmod'   => date(DATE_ATOM)
                ),
                'sitemapnews' => array(
                    'extension' => 'xml',
                    'lastmod'   => date(DATE_ATOM)
                )
            );

            //real sitemaps (in the toaster root)
            $sitemapFiles = Tools_Filesystem_Tools::findFilesByExtension($this->_helper->website->getPath(), 'xml', false, false, false);
            if(is_array($sitemapFiles) && !empty($sitemapFiles)) {
                foreach($sitemapFiles as $sitemapFile) {
                    if(preg_match('~sitemap.*\.xml.*~', $sitemapFile)) {
                        $fileInfo = pathinfo($this->_helper->website->getPath() . $sitemapFile);
                        if(is_array($fileInfo)) {
                            $sitemaps[$fileInfo['filename']] = array(
                                'extension' => $fileInfo['extension'],
                                'lastmod'   => date(DATE_ATOM, fileatime($this->_helper->website->getPath() . $sitemapFile))
                            );
                        }
                    }
                }
            }

            $this->view->sitemaps = $sitemaps;
        }
        $template = 'sitemap' . $sitemapType . '.xml.phtml';
	    if (null === ($sitemapContent = $this->_helper->cache->load($sitemapType, Helpers_Action_Cache::PREFIX_SITEMAPS))) {
            try {
                $sitemapContent = $this->view->render('backend/seo/' . $template);
            } catch (Zend_View_Exception $zve) {
		        // Try to find plugin's sitemap
		        try {
		            $sitemapContent = Tools_Plugins_Tools::runStatic('getSitemap', $sitemapType);
		            if(!$sitemapContent) {
		                $sitemapContent = Tools_Plugins_Tools::runStatic('getSitemap' . ucfirst($sitemapType));
		            }
		        } catch (Exception $e) {
			        Tools_System_Tools::debugMode() && error_log($e->getMessage());
			        $sitemapContent = false;
		        }

	            if ($sitemapContent === false){
		            $this->getResponse()->setHeader('Content-Type', 'text/html', true);
                    return $this->forward('index', 'index', null, array('page' => 'sitemap' . $sitemapType . '.xml'));
	            }
		    }
		    $this->_helper->cache->save($sitemapType, $sitemapContent, Helpers_Action_Cache::PREFIX_SITEMAPS, array('sitemaps'), Helpers_Action_Cache::CACHE_WEEK);
	    }
	    echo $sitemapContent;
   }

    /**
     * Serve news feeds
     *
     */
    public function feedsAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $type = filter_var($this->_request->getParam('type'), FILTER_SANITIZE_STRING);
        foreach (Tools_Plugins_Tools::getPluginsByTags(array('feed')) as $plugin) {
            if ($plugin->getTags() && in_array('feed', $plugin->getTags())) {
                $feedTool = ucfirst($plugin->getname()) . '_Tools_Feed';
                $feedXml  = $feedTool::getInstance()->generate($type);
                if (!empty($feedXml)) {
                    echo $feedXml;
                }
            }
        }

    }
}

