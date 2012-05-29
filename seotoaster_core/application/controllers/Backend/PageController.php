<?php
/**
 * Description of PageController
 *
 * @author iamne
 */
class Backend_PageController extends Zend_Controller_Action {

	public static $_allowedActions = array(
		'publishpages'
	);

	public function init() {
		parent::init();
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_PAGES) && !Tools_Security_Acl::isActionAllowed()) {
			$this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
		}
		$this->view->websiteUrl = $this->_helper->website->getUrl();
		$this->_helper->AjaxContext()->addActionContexts(array(
			'edit404page'      => 'json',
			'rendermenu'       => 'json',
			'listpages'        => 'json',
			'publishpages'     => 'json',
			'checkforsubpages' => 'json',
			'toggleoptimized'  => 'json'
		))->initContext('json');

	}

	public function pageAction() {
		$checkFaPull = false; //flag shows that system needs to check featured areas in session
		$pageForm    = new Application_Form_Page();
		$pageId      = $this->getRequest()->getParam('id');
		$mapper      = Application_Model_Mappers_PageMapper::getInstance();

		$pageForm->getElement('pageCategory')->addMultiOptions(array('Categories' => $mapper->selectCategoriesIdName()));

		$page = ($pageId) ? $mapper->find($pageId) : new Application_Model_Models_Page();

		if(!$this->getRequest()->isPost()) {
			if($page instanceof Application_Model_Models_Page) {
				$pageForm->setOptions($page->toArray());
				$pageForm->getElement('pageId')->setValue($page->getId());
				$pageForm->getElement('draft')->setValue($page->getDraft());

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
			$params   = $this->getRequest()->getParams();
			$messages = ($params['pageCategory'] == -4) ? array('pageCategory' => array('Please make your selection')) : array();

			//if page is optiized by samba unset optimized values from update
			if(isset($params['optimized']) && $params['optimized']) {
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

				$page->registerObserver(new Tools_Seo_Watchdog());
				$page->registerObserver(new Tools_Search_Watchdog());
				$page->registerObserver(new Tools_Page_GarbageCollector(array(
					'action' => Tools_System_GarbageCollector::CLEAN_ONUPDATE
				)));

				if($page->getId() && $page->getParentId() == 0 && $pageData['inMenu'] != Application_Model_Models_Page::IN_MAINMENU)  {
					if($this->_hasSubpages($page->getId())) {
						$this->_helper->response->fail($this->_helper->language->translate('Cannot downgrade the category.<br />This page is a category page and has subpages. Please remove or move subpages to another category first'));
						exit;
					}
				}


				$page = $this->_setAdditionalOptions($page, $pageData['pageOption']);
				unset($pageData['protected']);
				unset($pageData['is404page']);
				$page->setOptions($pageData);

				//prevent renaming of the index page
				if ($page->getUrl() != $this->_helper->website->getDefaultpage() ) {
					$page->setUrl($pageData['url']);
				}
				$page->setTargetedKey($page->getH1());
				$page->setParentId($pageData['pageCategory']);
				$page->setShowInMenu($pageData['inMenu']);


				$saveUpdateResult = $mapper->save($page);
				if($page->getId() == null) {
					$page->setId($saveUpdateResult);
				}

				if($checkFaPull) {
					$this->_processFaPull($saveUpdateResult);
				}

				// saving new page preview image is recieved it in request
				if (isset($params['pagePreviewImage']) && !empty ($params['pagePreviewImage'])) {
					$this->_processPagePreviewImage($page->getUrl(), $params['pagePreviewImage']);
				} // else updating existing
				elseif ($this->_helper->session->oldPageUrl != $page->getUrl()) {
					$this->_processPagePreviewImage($page->getUrl(), $this->_processPagePreviewImage($this->_helper->session->oldPageUrl));
				}

				$page->notifyObservers();

				$this->_helper->response->success(array('redirectTo' => $page->getUrl()));
				exit;
			}
			$messages = array_merge($pageForm->getMessages(), $messages);
			$this->_helper->response->fail(Tools_Content_Tools::proccessFormMessagesIntoHtml($messages, get_class($pageForm)));
			exit;
		}

		$this->view->faCount = ($page->getId()) ? sizeof(Application_Model_Mappers_FeaturedareaMapper::getInstance()->findAreasByPageId($page->getId())) : 0;

		//page preview image
		$this->view->pagePreviewImage = $this->_processPagePreviewImage($page->getUrl());
		$this->view->sambaOptimized   = $page->getOptimized();

        // page help section
        $this->view->helpSection = ($pageId) ? 'editpage' : 'addpage';

        if($page->getOptimized()) {
			$pageForm->lockFields(array('h1', 'headerTitle', 'url', 'navName', 'metaDescription', 'metaKeywords'));
		}
		$this->view->pageForm = $pageForm;
	}

	private function _setAdditionalOptions(Application_Model_Models_Page $page, $option) {
		$page->setIs404page(0)
			->setProtected(0)
			->setMemLanding(0)
			->setErrLoginLanding(0)
			->setCheckout(0)
			->setSignupLanding(0);
		switch ($option) {
			case Application_Model_Models_Page::OPT_404PAGE:
				$page->setIs404page(1);
			break;
			case Application_Model_Models_Page::OPT_PROTECTED:
				$page->setProtected(1);
			break;
			case Application_Model_Models_Page::OPT_ERRLAND:
				$page->setErrLoginLanding(1);
			break;
			case Application_Model_Models_Page::OPT_MEMLAND:
				$page->setMemLanding(1);
			break;
			case Application_Model_Models_Page::OPT_SIGNUPLAND:
				$page->setSignupLanding(1);
			break;
			case Application_Model_Models_Page::OPT_CHECKOUT:
				$page->setCheckout(1) ;
			break;
		}
		return $page;
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

	/**
	 * @todo Optimize this!
	 */
	public function rendermenuAction() {
		$menuType    = $this->getRequest()->getParam('mtype');
		$pageId      = $this->getRequest()->getParam('pId');
		$menuOptions = array();
		$menuHtml    = '';
		$mapper      = Application_Model_Mappers_PageMapper::getInstance();
		switch ($menuType) {
			case Application_Model_Models_Page::IN_MAINMENU:
				$categories = $mapper->selectCategoriesIdName();
				$menuOptions = array(
					'-4'         => 'Make your selection',
					'Seotoaster' => array(
						Application_Model_Models_Page::IDCATEGORY_CATEGORY => 'This page is a category',
						//Application_Model_Models_Page::IDCATEGORY_PRODUCT  => 'Product pages'
					)
				);
                if(is_array($categories) && !empty($categories)) {
                    $menuOptions['Categories'] = $categories;
                }
			break;
			case Application_Model_Models_Page::IN_STATICMENU:
				$menuOptions = array(Application_Model_Models_Page::IDCATEGORY_DEFAULT => 'Make your selection');
			break;
			case Application_Model_Models_Page::IN_NOMENU:
				$menuOptions = array(Application_Model_Models_Page::IDCATEGORY_DEFAULT => 'Make your selection');
			break;
		}
		$selectHelper = $this->view->getHelper('formSelect');

		if($pageId) {
			$currPage = $mapper->find($pageId);
		}

		$this->view->select = $selectHelper->formSelect('pageCategory', (isset($currPage) ? $currPage->getParentId() : ''), null, $menuOptions);
	}

	public function edit404pageAction() {
		$notFoundPage = Application_Model_Mappers_PageMapper::getInstance()->find404Page();
		$this->view->notFoundUrl = ($notFoundPage instanceof Application_Model_Models_Page) ? $notFoundPage->getUrl() : '';
	}

	public function draftAction() {
		$this->view->draftPages = Tools_Page_Tools::getDraftPages();
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
				$tree[] = array(
					'category' => $category,
					'pages'    => $pageMapper->findByParentId($category->getId())
				);
			}
			$this->view->tree = $tree;
		}
		$this->view->staticMenu = $pageMapper->fetchAllStaticMenuPages();
		$this->view->noMenu     = $pageMapper->fetchAllNomenuPages();
	}

	private function _processPagePreviewImage($pageUrl, $tmpPreviewFile = null){
		$websiteConfig      = Zend_Registry::get('website');
		$pageUrl            = str_replace(DIRECTORY_SEPARATOR, '-', $this->_helper->page->clean($pageUrl));
		$previewPath        = $websiteConfig['path'] .$websiteConfig['preview'];
		$filelist           = Tools_Filesystem_Tools::findFilesByExtension($previewPath, '(jpg|gif|png)', false, false, false);
		$currentPreviewList = preg_grep('/^'.$pageUrl.'\.(png|jpg|gif)$/i', $filelist);

		if ($tmpPreviewFile) {
			$tmpPreviewFile = $websiteConfig['path'] . str_replace($this->_helper->website->getUrl(), '', $tmpPreviewFile);
			if (is_file($tmpPreviewFile) && is_readable($tmpPreviewFile)){
				preg_match('/\.[\w\d]{2,6}$/', $tmpPreviewFile, $extension);
				$newPreviewImageFile = $websiteConfig['path'].$websiteConfig['preview'].$pageUrl.$extension[0];

				//cleaning form existing page previews
				if(!empty($currentPreviewList)) {
					foreach ($currentPreviewList as $key => $file) {
						if(file_exists($previewPath . $file)) {
							if (Tools_Filesystem_Tools::deleteFile($previewPath.$file)){
								unset($currentPreviewList[0]);
							}
						}
					}
				}

				if (is_writable($newPreviewImageFile)){
					$status = @rename($tmpPreviewFile, $newPreviewImageFile);
				} else {
					$status = @copy($tmpPreviewFile, $newPreviewImageFile);
				}
				if ($status && file_exists($tmpPreviewFile)) {
					Tools_Filesystem_Tools::deleteFile($tmpPreviewFile);
				}

				$miscConfig = Zend_Registry::get('misc');

				// unlink old croped page previews
				if(!empty($currentPreviewList)) {
					foreach($currentPreviewList as $fileToUnlink) {
						$unlinkPath = $this->_helper->website->getPreviewCrop() . $fileToUnlink;
						if(file_exists($unlinkPath)) {
							unlink($unlinkPath);
						}
					}
				}

				Tools_Image_Tools::resize($newPreviewImageFile, $miscConfig['pageTeaserCropSize'], false, $this->_helper->website->getPreviewCrop(), true);
				unset($miscConfig);

				return $this->_helper->website->getUrl() . $websiteConfig['preview'] . $pageUrl . $extension[0];
			}
		}

		if (sizeof($currentPreviewList) == 0){
			return false;
		} else {
			$pagePreviewImage = $this->_helper->website->getUrl() . $websiteConfig['preview'] . reset($currentPreviewList);
		}

		return $pagePreviewImage;
	}

	public function listpagesAction() {
		$where        = $this->_getProductCategoryPageWhere();
		$templateName = $this->getRequest()->getParam('template', '');
		if($templateName) {
			$this->view->templateName = $templateName;
			$where                    = 'template_id="' . $templateName . '"';
		}
		$pages    = Application_Model_Mappers_PageMapper::getInstance()->fetchAll($where, array('h1 ASC'));
		$sysPages = Application_Model_Mappers_PageMapper::getInstance()->fetchAll($where, array('h1 ASC'), true);
		$pages    = array_merge((array)$pages, (array)$sysPages);
		$this->view->responseData = array_map(function($page) {
			return $page->toArray();
		}, $pages);
	}

	public function linkslistAction() {
		//external_link_list_url

		$this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();


		$externalLinksContent = 'var tinyMCELinkList = new Array(';
		$pages = Application_Model_Mappers_PageMapper::getInstance()->fetchAll($this->_getProductCategoryPageWhere(), array('h1'));
		if(!empty ($pages)) {
			foreach ($pages as $page) {
				$externalLinksContent .= '["'
					. $page->getH1()
					. '", "'
					. $this->_helper->website->getUrl() . $page->getUrl()
					. '"],';
			}
			$externalLinksContent = substr($externalLinksContent, 0, -1) . ');';
			$this->getResponse()->setRawHeader('Content-type: text/javascript')
				->setRawHeader('pragma: no-cache')
				->setRawHeader('expires: 0')
				->setBody($externalLinksContent)
				->sendResponse();
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
			$this->_cache->clean(Helpers_Action_Cache::KEY_DRAFT, Helpers_Action_Cache::PREFIX_DRAFT);
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
			'url'             => $page->getUrl(),
			'metaDescription' => $page->getMetaDescription(),
			'metaKeywords'    => $page->getMetaKeywords()
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

	private function _restoreOriginalValues($pageData) {
		$page = Application_Model_Mappers_PageMapper::getInstance()->find($pageData['pageId'], true);
		$pageData['h1']              = $page->getH1();
		$pageData['headerTitle']     = $page->getHeaderTitle();
		$pageData['navName']         = $page->getNavName();
		$pageData['url']             = $page->getUrl();
		$pageData['metaKeywords']    = $page->getMetaKeywords();
		$pageData['metaDescription'] = $page->getMetaDescription();
		unset($page);
		return $pageData;
	}
}

