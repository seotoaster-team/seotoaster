<?php
/**
 * Description of PageController
 *
 * @author iamne
 */
class Backend_PageController extends Zend_Controller_Action {

    const DEFAULT_TEMPLATE = 'default';

    public static $_allowedActions = array('publishpages');

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

        $secureToken = Tools_System_Tools::initZendFormCsrfToken($pageForm, Tools_System_Tools::ACTION_PREFIX_PAGES);

        $this->view->secureToken = $secureToken;

        $this->view->pageId = '';
        $this->view->pageType = '1';
        if ($pageId) {
            // search page by id
            $page = $mapper->find($pageId);
            $this->view->pageId = $pageId;
            $this->view->pageType = $page->getPageType();
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
            $externalLink = (isset($params['externalLinkStatus']) && $params['externalLinkStatus']);

            //if page is optimized by samba unset optimized values from update
            if($optimized) {
                $params = $this->_restoreOriginalValues($params);
            }

            if($externalLink && !$optimized){
                $params = $this->_processParamsForExternalLink($params);
            }

            $pageForm = Tools_System_Tools::addTokenValidatorZendForm($pageForm, Tools_System_Tools::ACTION_PREFIX_PAGES);

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
                    $optionsMapper = Application_Model_Mappers_PageOptionMapper::getInstance();
                    $pageOptions = $optionsMapper->fetchOptions(false, true);
                    if (array_key_exists($pageData['extraOptions'], $pageOptions)) {
                        $optionsMapper->deletePageHasOption(
                            $pageData['extraOptions']
                        );
                    }
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

                //if unset draft category publish all pages
                if($mapper->isDraftCategory($params['pageId']) && $params['draft'] == 0){
                    $mapper->publishChildPages($params['pageId']);
                }

                $page = $mapper->save($page);

                if($checkFaPull) {
                    $this->_processFaPull($page->getId());
                }

                $page->notifyObservers();

                $redirectTo = $page->getUrl();
                if ($externalLink && !$optimized) {
                    $redirectTo = 'index.html';
                }
                $this->_helper->response->success(array('redirectTo' => $redirectTo));
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
        if($this->getRequest()->isDelete()){
            $pageMapper = Application_Model_Mappers_PageMapper::getInstance();
            $ids        = explode(',' , $this->getRequest()->getParam('id'));
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
        $pageDbTable = new Application_Model_DbTable_Page();
        if($this->getRequest()->isPost()) {
            $act = $this->getRequest()->getParam('act');
            if(!$act) {
                exit;
            }
            $tokenToValidate = $this->getRequest()->getParam(Tools_System_Tools::CSRF_SECURE_TOKEN, false);
            $valid = Tools_System_Tools::validateToken($tokenToValidate, Tools_System_Tools::ACTION_PREFIX_ORGANIZEPAGES);
            if (!$valid) {
                exit;
            }
            switch($act) {
                case 'save':
                    $orderedList = array_unique(Zend_Json::decode($this->getRequest()->getParam('ordered'), Zend_Json::TYPE_ARRAY));
                    unset ($orderedList[array_search(Application_Model_Models_Page::IDCATEGORY_DEFAULT, $orderedList)]);
                    if(is_array($orderedList)) {
                        $updatePageOrderSql = "UPDATE ".$pageDbTable->info('name')." SET `order` = :order WHERE `id` = :id ";
                        $stmt = $pageDbTable->getAdapter()->prepare($updatePageOrderSql);
                        foreach ($orderedList as $key => $pageId) {
                            $stmt->bindParam('order', $key);
                            $stmt->bindParam('id', $pageId);
                            $stmt->execute();
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
        $secureToken = Tools_System_Tools::initSecureToken(Tools_System_Tools::ACTION_PREFIX_ORGANIZEPAGES);
        $this->view->secureToken = $secureToken;
        $this->view->helpSection = 'organize';
        $this->view->staticMenu  = $pageMapper->fetchAllStaticMenuPages();
        $this->view->noMenu      = $pageMapper->fetchAllNomenuPages();
    }

    public function listpagesAction() {
        $where        = $this->_getProductCategoryPageWhere();
        $templateName = filter_var($this->getRequest()->getParam('template', ''), FILTER_SANITIZE_STRING);
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
        $tokenToValidate = $this->getRequest()->getParam(Tools_System_Tools::CSRF_SECURE_TOKEN, false);
        $valid = Tools_System_Tools::validateToken($tokenToValidate, Tools_System_Tools::ACTION_PREFIX_PAGES);
        if (!$valid) {
            $this->_helper->response->fail('');
        }
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

    /**
     * Prepare page params with external link
     *
     * @param array $params
     * @return array
     */
    private function _processParamsForExternalLink(array $params)
    {
        $page = Application_Model_Mappers_PageMapper::getInstance()->find($params['pageId'], true);
        $params['externalLink'] = $params['url'];
        if (!empty($params['externalLink']) && !preg_match('~(http|https|ftp):\/\/~', $params['externalLink'])) {
            $params['externalLink'] = 'http://' . $params['externalLink'];
        }
        if ($page instanceof Application_Model_Models_Page) {
            $params['url'] = $page->getUrl();
            $params['metaKeywords'] = $page->getMetaKeywords();
            $params['headerTitle'] = $page->getHeaderTitle();
            $params['h1'] = $page->getHeaderTitle();
            $params['metaDescription'] = $page->getMetaDescription();
            $params['templateId'] = $page->getTemplateId();
            if (!$page->getExternalLinkStatus()) {
                $this->_helper->cache->clean();
            }

        } else {
            $params['templateId'] = self::DEFAULT_TEMPLATE;
            $params['h1'] = $params['navName'];
            $params['headerTitle'] = self::DEFAULT_TEMPLATE;
            $this->_helper->cache->clean();
        }
        return $params;
    }
    /**
     * Checks if the category is draft
     */
    public function isDraftCategoryAction()
    {
        if ($this->getRequest()->isPost()) {
            $categoryID = $this->getRequest()->getPost('id', null);
            $this->_helper->response->success(Application_Model_Mappers_PageMapper::getInstance()->isDraftCategory($categoryID));
        }

    }

    public function switchindexpageAction()
    {
        $data = $this->_request->getParams();
        if ($this->getRequest()->isPost() && !empty($data['pageId'])) {
            $tokenToValidate = $this->getRequest()->getParam(Tools_System_Tools::CSRF_SECURE_TOKEN, false);
            $valid = Tools_System_Tools::validateToken($tokenToValidate, Tools_System_Tools::ACTION_PREFIX_PAGES);
            if (!$valid) {
                $this->_helper->response->fail($this->_helper->language->translate('Token expired'));
            }
            $currentPageId = $data['pageId'];
            $pageMapper = Application_Model_Mappers_PageMapper::getInstance();

            $indexRenamedUrl = 'index-old.html';
            $pageToRenameModel = $pageMapper->find($currentPageId);

            if (!$pageToRenameModel instanceof Application_Model_Models_Page) {
                $this->_helper->response->fail($this->_helper->language->translate('Page doesn\'t exist'));
            }

            $pageToRenameUrl = $pageToRenameModel->getUrl();
            if ($pageToRenameUrl === 'index.html') {
                $this->_helper->response->fail($this->_helper->language->translate('You can\'t assign index as index'));
            }

            $pageToRenameOptimized = $pageToRenameModel->getOptimized();
            if (!empty($pageToRenameOptimized)) {
                $this->_helper->response->fail($this->_helper->language->translate('This page is optimized. Remove the optimization before assigning it as Index page'));
            }

            $pageToRenameExternalLink = $pageToRenameModel->getExternalLinkStatus();
            if (!empty($pageToRenameExternalLink)) {
                $this->_helper->response->fail($this->_helper->language->translate('This page leads to an external site. Remove the link before assigning it as Index page'));
            }

            $pageToRenamePageType = $pageToRenameModel->getPageType();
            if ($pageToRenamePageType !== '1') {
                $this->_helper->response->fail($this->_helper->language->translate('You can\'t assign not regular pages as index page'));
            }

            $pageToRenameParentId = $pageToRenameModel->getParentId();
            if ($pageToRenameParentId === '0') {
                $this->_helper->response->fail($this->_helper->language->translate('A category page can\'t be assigned as Index page'));
            }

            $pageToRenamePageOptions = $pageToRenameModel->getExtraOptions();
            if (!empty($pageToRenamePageOptions)) {
                $this->_helper->response->fail($this->_helper->language->translate('This page has external options. Remove them before assign it as Index page'));
            }

            $oldIndexPageModel = $pageMapper->findByUrl($indexRenamedUrl);
            if ($oldIndexPageModel instanceof Application_Model_Models_Page) {
                $this->_helper->response->fail($this->_helper->language->translate('You already have index-old.html please remove or rename it'));
            }

            //processing original index page to temporary index-old page
            $indexPageModel = $pageMapper->findByUrl('index.html');
            if (!$indexPageModel instanceof Application_Model_Models_Page) {
                $this->_helper->response->fail($this->_helper->language->translate('Index page missing'));
            }

            $indexOptimized = $indexPageModel->getOptimized();
            if (!empty($indexOptimized)) {
                $this->_helper->response->fail($this->_helper->language->translate('Index page has optimization. Please remove it'));
            }

            $indexPageModel->setUrl($indexRenamedUrl);

            $indexPreviewImagePath = $this->_helper->website->getPath() . $this->_helper->website->getPreview() . $indexPageModel->getPreviewImage();
            $indexPreviewImageName = Tools_Page_Tools::processPagePreviewImage($indexRenamedUrl,
                $indexPreviewImagePath);
            $indexPageModel->setPreviewImage($indexPreviewImageName);

            $pageMapper->save($indexPageModel);

            //processing new index page
            $newIndexPreviewImagePath = $this->_helper->website->getPath() . $this->_helper->website->getPreview() . $pageToRenameModel->getPreviewImage();
            $newPreviewIndexImageName = Tools_Page_Tools::processPagePreviewImage('index.html',
                $newIndexPreviewImagePath);
            $pageToRenameModel->setUrl('index.html');
            $pageToRenameModel->setPreviewImage($newPreviewIndexImageName);
            $pageToRenameModel->setParentId('0');
            $pageToRenameModel->setShowInMenu('1');
            $pageMapper->save($pageToRenameModel);


            //processing temporary index page to the renamed page
            $renamedIndexPageModel = $pageMapper->findByUrl('index-old.html');
            if (!$renamedIndexPageModel instanceof Application_Model_Models_Page) {
                $this->_helper->response->fail($this->_helper->language->translate('Temporary index page not found'));
            }
            $indexRenamedImagePath = $this->_helper->website->getPath() . $this->_helper->website->getPreview() . $renamedIndexPageModel->getPreviewImage();
            $previewIndexRenamedImageName = Tools_Page_Tools::processPagePreviewImage($pageToRenameUrl,
                $indexRenamedImagePath);
            $renamedIndexPageModel->setUrl($pageToRenameUrl);
            $renamedIndexPageModel->setPreviewImage($previewIndexRenamedImageName);
            $renamedIndexPageModel->setParentId('-1');
            $pageMapper->save($renamedIndexPageModel);

            $this->_helper->cache->clean();

            $this->_helper->response->success($this->_helper->language->translate('Switch has been done'));

        }
    }
}

