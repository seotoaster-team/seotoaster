<?php
/**
 * Description of PageController
 *
 * @author iamne
 */
class Backend_PageController extends Zend_Controller_Action {

    public static $_allowedActions = array('publishpages', 'listpages');

    protected $_mapper             = null;

    public function init() {
        if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_PAGES) && !Tools_Security_Acl::isActionAllowed(Tools_Security_Acl::RESOURCE_CONTENT)) {
            $this->redirect($this->_helper->website->getUrl(), array('exit' => true));
        }
        $this->view->websiteUrl = $this->_helper->website->getUrl();

        if ('' == $this->getRequest()->getParam('format', '')) {
            $this->getRequest()->setParam('format', 'json');
        }

        /* @var Zend_Controller_Action_Helper_ContextSwitch $contextSwitch */
        $this->_helper->contextSwitch
            ->addContext('html', array('suffix' => 'html', 'headers' => array('Content-Type' => 'text/html')))
            ->addActionContexts(array(
            'edit404page'      => 'json',
            'rendermenu'       => 'json',
            'listpages'        => array('json', 'html'),
            'publishpages'     => 'json',
            'checkforsubpages' => 'json',
            'toggleoptimized'  => 'json'
            ))
            ->initContext();
    }

    public function pageAction() {
        $checkFaPull = false; //flag shows that system needs to check featured areas in session
        $pageForm    = new Application_Form_Page();
        $pageId      = $this->getRequest()->getParam('id');
        $mapper      = Application_Model_Mappers_PageMapper::getInstance();

        if ($pageId) {
            // search page by id
            $page = $mapper->find($pageId);
        } else {
            // load new page
            $page = new Application_Model_Models_Page(array('showInMenu' => Application_Model_Models_Page::IN_MAINMENU));
        }

        if(!$this->getRequest()->isPost()) {
            $pageForm->getElement('pageCategory')->addMultiOptions($this->_getMenuOptions($page));
            $pageForm->getElement('pageCategory')->setValue($page->getParentId());

            if($page instanceof Application_Model_Models_Page) {
                $pageForm->setOptions($page->toArray());
                $pageForm->getElement('pageId')->setValue($page->getId());
                $pageForm->getElement('draft')->setValue($page->getDraft());

                //will be like this for now until page will support multiple options set (from the interface)
                $pageOptions = $page->getExtraOptions();
                $pageForm->getElement('extraOptions')->setValue(isset($pageOptions[0]) ? $pageOptions[0] : 0);

                $defaultPageUrl = $this->_helper->website->getDefaultpage();
                if($pageForm->getElement('url')->getValue() == $this->_helper->page->clean($defaultPageUrl)) {
                    $pageForm->getElement('url')->setAttribs(array(
                        'readonly' => true,
                        'class'    => 'noedit'
                    ));
                }
            }
        }
        else {
            $params    = $this->getRequest()->getParams();
            $messages  = ($params['pageCategory'] == -4) ? array('pageCategory' => array('Please make your selection')) : array();
            $optimized = (isset($params['optimized']) && $params['optimized']);

            //if page is optimized by samba unset optimized values from update
            if($optimized) {
                $params = $this->_restoreOriginalValues($params);
            }

            if($pageForm->isValid($params)) {
                $pageData        = $pageForm->getValues();
                $pageData['url'] =  $this->_helper->page->filterUrl($pageData['url']);
                //if we'r creating page -> check that we do not have an identical urls
                if(!$pageId) {
                    $pageExists = $mapper->findByUrl($pageData['url']);
                    if($pageExists instanceof Application_Model_Models_Page) {
                        $this->_helper->response->fail('Page with url <strong>' . $pageData['url'] . '</strong> already exists.');
                        exit;
                    }
                    $checkFaPull = true;
                }

                //saving old data for seo routine
                $this->_helper->session->oldPageUrl   = $page->getUrl();
                $this->_helper->session->oldPageH1    = $page->getH1();
                $this->_helper->session->oldPageDraft = $page->getDraft();

                if(!$optimized) {
                    $page->registerObserver(new Tools_Seo_Watchdog());
                }

                $page->registerObserver(new Tools_Search_Watchdog());
                $page->registerObserver(new Tools_Page_GarbageCollector(array(
                    'action' => Tools_System_GarbageCollector::CLEAN_ONUPDATE
                )));

                if($page->getId() && $page->getParentId() == 0 && ($pageData['inMenu'] != Application_Model_Models_Page::IN_MAINMENU || $pageData['pageCategory'] != 0))  {
                    if($this->_hasSubpages($page->getId())) {
                        $this->_helper->response->fail($this->_helper->language->translate('Cannot downgrade the category.<br />This page is a category page and has subpages. Please remove or move subpages to another category first'));
                        exit;
                    }
                }

                //Analyze if system have options one time used
                if ($pageData['removePreviousOption'] === '' && !empty($pageData['extraOptions'])) {
                    $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
                    $options = Application_Model_Mappers_PageOptionMapper::getInstance()->checkOptionUsage(
                        $pageData['extraOptions'],
                        $pageData['url']
                    );

                    if (!empty($options)) {
                        $code = 200;
                        $responseData = Zend_Json::encode(
                            array(
                                'error' => 1,
                                'responseText' => $this->_helper->language->translate(
                                    'Ohhhlaaaa! A page with this option already exists '
                                ) . '<a target="_blank" href="' . $websiteHelper->getUrl(
                                ) . $options['url'] . '">' . $this->_helper->language->translate(
                                    '(see it here)'
                                ) . '</a> ' . $this->_helper->language->translate(
                                    'Is it okay to replace it with this one?'
                                ),
                                'dialog' => true,
                                'httpCode' => $code
                            )
                        );
                        $response = $this->getResponse();
                        $response->setHttpResponseCode($code)
                            ->setBody($responseData)
                            ->setHeader('Content-Type', 'application/json', true);
                        $response->sendResponse();
                        exit;
                    }

                } else {
                    //Removing page options that have one time options
                    Application_Model_Mappers_PageOptionMapper::getInstance()->deletePageHasOption(
                        $pageData['extraOptions']
                    );
                }

                $page->setOptions($pageData);

                //prevent renaming of the index page
                if ($page->getUrl() != $this->_helper->website->getDefaultpage() ) {
                    $page->setUrl($pageData['url']);
                }
                $page->setTargetedKeyPhrase($page->getH1());
                $page->setParentId($pageData['pageCategory']);
                $page->setShowInMenu($pageData['inMenu']);

	             // saving new page preview image is recieved it in request
                if (isset($params['pagePreviewImage']) && !empty ($params['pagePreviewImage'])) {
                    $previewImageName = Tools_Page_Tools::processPagePreviewImage((!$optimized) ? $page->getUrl() : $this->_helper->session->oldPageUrl, $params['pagePreviewImage']);
                } // else updating existing
                elseif (!$optimized && $this->_helper->session->oldPageUrl !== $page->getUrl()) {
                    // TODO: Refactor this part
	                $previewImageName = Tools_Page_Tools::processPagePreviewImage((!$optimized) ? $page->getUrl() : $this->_helper->session->oldPageUrl, Tools_Page_Tools::processPagePreviewImage($this->_helper->session->oldPageUrl));
                }

	            if(isset($previewImageName)) {
                    $page->setPreviewImage($previewImageName);
                }

                if ((bool) $this->_helper->config->getConfig('enableDeveloperMode')) {
                    // Add template if not in the database
                    if (null === Application_Model_Mappers_TemplateMapper::getInstance()->find($page->getTemplateId())) {
                        $themesConfig = Zend_Registry::get('theme');
                        $themePath = $this->_helper->website->getPath().$themesConfig['path'].$this->_helper->config->getConfig('currentTheme');
                        Tools_Theme_Tools::addTemplates($themePath, array($page->getTemplateId().'.html'));
                        Tools_Theme_Tools::updateThemeIni(
                            $themePath,
                            $page->getTemplateId(),
                            Application_Model_Models_Template::TYPE_REGULAR
                        );
                    }
                }

                $page = $mapper->save($page);

                if($checkFaPull) {
                    $this->_processFaPull($page->getId());
                }

                $page->notifyObservers();

                $this->_helper->response->success(array('redirectTo' => $page->getUrl()));
                exit;
            }
            $messages = array_merge($pageForm->getMessages(), $messages);
            $this->_helper->response->fail(Tools_Content_Tools::proccessFormMessages($messages));
            exit;
        }

        $this->view->faCount = ($page->getId()) ? sizeof(Application_Model_Mappers_FeaturedareaMapper::getInstance()->findAreasByPageId($page->getId())) : 0;

        //page preview image
        $this->view->pagePreviewImage = Tools_Page_Tools::getPreview($page);//Tools_Page_Tools::processPagePreviewImage($page->getUrl());
        $this->view->sambaOptimized   = $page->getOptimized();

        // page help section
        $this->view->helpSection = ($pageId) ? 'editpage' : 'addpage';

        if($page->getOptimized()) {
            $pageForm->lockFields(array('h1', 'headerTitle', 'url', 'navName', 'metaDescription', 'metaKeywords', 'teaserText'));
        }
        $this->view->pageForm = $pageForm;
    }

    private function _processFaPull($pageId) {
        if(isset ($this->_helper->session->faPull)) {
            $faPull = $this->_helper->session->faPull;
            foreach ($faPull as $key => $faId) {
                $fa = Application_Model_Mappers_FeaturedareaMapper::getInstance()->find($faId, false);
                $fa->addPage(Application_Model_Mappers_PageMapper::getInstance()->find($pageId));
                Application_Model_Mappers_FeaturedareaMapper::getInstance()->save($fa);
                unset($fa);
            }
            unset($this->_helper->session->faPull);
        }
    }

    public function checkforsubpagesAction() {
        $this->_helper->response->success(array(
            'subpages' => $this->_hasSubpages($this->getRequest()->getParam('pid')),
            'message'  => '<h2>' . $this->_helper->language->translate('This page is a category and has subpages.') . '</h2>' . $this->_helper->language->translate('Please remove or move subpages to another category first')
        ));
    }

    public function deleteAction() {
        if($this->getRequest()->isPost()) {
            $pageMapper = Application_Model_Mappers_PageMapper::getInstance();
            $ids        = (array)$this->getRequest()->getParam('id');
            if(empty ($ids)) {
                $this->_helper->response->fail($this->_helper->language->translate('Page id is ot specified'));
                exit;
            }
            foreach ($ids as $pageId) {
                $page = $pageMapper->find(intval($pageId));
                if(!$page instanceof Application_Model_Models_Page) {
                    $this->_helper->response->fail($this->_helper->language->translate('Cannot find page to remove.'));
                    exit;
                }
                //check if page is a category and it has subpages prevent removing the page
                if($page->getParentId() == 0) {
                    if($this->_hasSubpages($page->getId())) {
                        $this->_helper->response->fail(array(
                            'title' => $this->_helper->language->translate('Unable to remove the page'),
                            'body'  => $this->_helper->language->translate('<h2>The page: "' . $page->getNavName() .'" is a category page and has subpages.</h2><br />Please remove or move subpages to another category first')
                        ));
                        exit;
                    }
                }
                $page->registerObserver(new Tools_Page_GarbageCollector(array(
                    'action' => Tools_System_GarbageCollector::CLEAN_ONDELETE
                )));
                $pageMapper->delete($page);
                unset($page);
            }
            $this->_helper->response->success($this->_helper->language->translate('Page(s) removed.'));
        }
    }

    protected function _getMenuOptions($page = null) {
        $categories = Application_Model_Mappers_PageMapper::getInstance()->selectCategoriesIdName(true);
        if($page instanceof Application_Model_Models_Page && $page->getParentId() == 0) {
            unset($categories[$page->getId()]);
        }
        return array(
            '-4'         => 'Make your selection',
            'Seotoaster' => array(
                Application_Model_Models_Page::IDCATEGORY_CATEGORY => 'This page is a category',
            ),
            'Categories' => $categories
        );
    }

    public function edit404pageAction() {
        $notFoundPage = Application_Model_Mappers_PageMapper::getInstance()->find404Page();
        $this->view->notFoundUrl = ($notFoundPage instanceof Application_Model_Models_Page) ? $notFoundPage->getUrl() : '';
    }

    public function draftAction() {
        $this->view->helpSection = 'draft';
        $this->view->draftPages  = Tools_Page_Tools::getDraftPages();
    }

    public function organizeAction() {
        $pageMapper = Application_Model_Mappers_PageMapper::getInstance();

        if($this->getRequest()->isPost()) {
            $act = $this->getRequest()->getParam('act');
            if(!$act) {
                exit;
            }
            switch($act) {
                case 'save':
                    $orderedList = array_unique($this->getRequest()->getParam('ordered'));
                    unset ($orderedList[array_search(Application_Model_Models_Page::IDCATEGORY_DEFAULT, $orderedList)]);
                    if(is_array($orderedList)) {
                        foreach ($orderedList as $key => $pageId) {
                            $page = $pageMapper->find($pageId);
                            $page->setOrder($key);
                            $pageMapper->save($page);
                        }
                        $this->_helper->cache->clean(false, false, 'Widgets_Menu_Menu');
                        $this->_helper->response->success($this->_helper->language->translate('New order saved'));
                    }
                    $this->_helper->response->fail($this->_helper->language->translate('Can\'t save order. List is broken'));
                    break;
                case 'renew':
                    $newCategoryId = $this->getRequest()->getParam('categoryId');
                    $pagesList     = $this->getRequest()->getParam('pages');
                    $menu          = $this->getRequest()->getParam('menu');
                    foreach ($pagesList as $pageId) {
                        $page = $pageMapper->find($pageId);
                        $page->setParentId($newCategoryId);
                        $page->setShowInMenu($menu);
                        $pageMapper->save($page);
                    }
                    break;

                default:
                    break;
            }
            exit;
        }

        $tree = array();
        $categories = $pageMapper->findByParentId(0);
        if(is_array($categories) && !empty ($categories)) {
            foreach ($categories as $category) {
	            // TODO: remove next check and code something smart
	            if ($category->getDraft()){
		            continue;
	            }
                $tree[] = array(
                    'category' => $category,
                    'pages'    => $pageMapper->findByParentId($category->getId())
                );
            }
            $this->view->tree = $tree;
        }
        $this->view->helpSection = 'organize';
        $this->view->staticMenu  = $pageMapper->fetchAllStaticMenuPages();
        $this->view->noMenu      = $pageMapper->fetchAllNomenuPages();
    }

    public function listpagesAction() {
        $where        = $this->_getProductCategoryPageWhere();
        $templateName = $this->getRequest()->getParam('template', '');
        if($templateName) {
            $this->view->templateName = $templateName;
            $where                    = 'template_id="' . $templateName . '"';
        }
        if($this->getRequest()->getParam('categoryName', false)) {
            $page = Application_Model_Mappers_PageMapper::getInstance()->findByNavName($this->getRequest()->getParam('categoryName'));
            $pageId = $page->getId();
        }
        elseif($this->getRequest()->getParam('pageId', false)) {
            $pageId = $this->getRequest()->getParam('pageId');
        }

        if(isset($pageId) && $pageId) {
            if($where == null) {
                $where .= ' parent_id ="' . $pageId . '"';
            }
            else {
                $where .= ' AND parent_id ="' . $pageId . '"';
            }
        }

        $pages    = Application_Model_Mappers_PageMapper::getInstance()->fetchAll($where, array('h1 ASC'));
        $sysPages = Application_Model_Mappers_PageMapper::getInstance()->fetchAll($where, array('h1 ASC'), true);
        $pages    = array_merge((array)$pages, (array)$sysPages);
        $this->view->responseData = array_map(function($page) {
            return $page->toArray();
        }, $pages);
    }

    public function linkslistAction() {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();

        $where = $this->_getProductCategoryPageWhere();
        $whereQuote = $this->_getQuotePageWhere();
        if($where !== null && $whereQuote !== null) {
            $where .= ' AND ' . $whereQuote;
        }
        else {
            $where .= $whereQuote;
        }
        $pages = Application_Model_Mappers_PageMapper::getInstance()->fetchAll($where, array('h1'));
        if(!empty ($pages)) {
            $links = array();
            foreach ($pages as $page) {
                if ($page->getExtraOption(Application_Model_Models_Page::OPT_404PAGE)) {
                    continue;
                }
                array_push($links, array('title'=>$page->getH1(), 'value'=>$this->_helper->website->getUrl() . $page->getUrl()));
            }
            $this->getResponse()->setBody(Zend_Json::encode($links));
        }
    }

    public function publishpagesAction() {
        $pages           = Application_Model_Mappers_PageMapper::getInstance()->fetchAllDraftPages();
        $cleanDraftCache = false;
        foreach($pages as $page) {
            if(($page->getPublishAt() !== null) && ( (time() - strtotime($page->getPublishAt()))  >= 0)) {
                $cleanDraftCache = true;
                $page->setPublishAt(null);
                $page->setDraft(false);
                Application_Model_Mappers_PageMapper::getInstance()->save($page);
            }
        }
        if($cleanDraftCache) {
            $this->_cache->clean(false, false, Helpers_Action_Cache::TAG_DRAFT);
        }
    }

    /**
     * Toggle fields values between original and optimized
     * @throws Exceptions_SeotoasterException
     */
    public function toggleoptimizedAction() {
        if(!$this->getRequest()->isPost()) {
            throw new Exceptions_SeotoasterException('Direct access is not allowed.');
        }
        $optimized        = $this->getRequest()->getParam('optimized');
        $pageId           = $this->getRequest()->getParam('pid');
        $page             = Application_Model_Mappers_PageMapper::getInstance()->find($pageId, !$optimized);
        $this->view->data = array(
            'h1'              => $page->getH1(),
            'headerTitle'     => $page->getHeaderTitle(),
            'navName'         => $page->getNavName(),
            'url'             => $this->_helper->page->clean($page->getUrl()),
            'metaDescription' => $page->getMetaDescription(),
            'metaKeywords'    => $page->getMetaKeywords(),
            'teaserText'      => $page->getTeaserText()
        );
    }

    private function _hasSubpages($pageId) {
        $subpages = Application_Model_Mappers_PageMapper::getInstance()->findByParentId($pageId);
        return sizeof($subpages);
    }

    private function _getProductCategoryPageWhere() {
        $productCategoryPage = Tools_Page_Tools::getProductCategoryPage();
        return (($productCategoryPage instanceof Application_Model_Models_Page) ? 'parent_id != "' . $productCategoryPage->getId() . '"' : null);
    }

    private function _getQuotePageWhere() {
        $quotePlugin = Application_Model_Mappers_PluginMapper::getInstance()->findByName('quote');
        if($quotePlugin !== null && $quotePlugin->getStatus() === Application_Model_Models_Plugin::ENABLED) {
            return 'parent_id != "' . Quote::QUOTE_CATEGORY_ID . '"';
        }
        return null;
    }

    private function _restoreOriginalValues($pageData) {
        $page = Application_Model_Mappers_PageMapper::getInstance()->find($pageData['pageId'], true);
        $pageData['h1']              = $page->getH1();
        $pageData['headerTitle']     = $page->getHeaderTitle();
        $pageData['navName']         = $page->getNavName();
        $pageData['url']             = $this->_helper->page->clean($page->getUrl());  // TODO: review this part
        $pageData['metaKeywords']    = $page->getMetaKeywords();
        $pageData['metaDescription'] = $page->getMetaDescription();
        unset($page);
        return $pageData;
    }
}

