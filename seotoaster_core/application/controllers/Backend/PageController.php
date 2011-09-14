<?php
/**
 * Description of PageController
 *
 * @author iamne
 */
class Backend_PageController extends Zend_Controller_Action {

	public function init() {
		parent::init();
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_PAGES)) {
			$this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
		}
		$this->view->websiteUrl = $this->_helper->website->getUrl();
		$this->_helper->AjaxContext()->addActionContext('edit404page', 'json')->initContext('json');
		$this->_helper->AjaxContext()->addActionContext('rendermenu', 'json')->initContext('json');
		$this->_helper->AjaxContext()->addActionContext('listpages', 'json')->initContext('json');
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
			}
		}
		else {
			$params = $this->getRequest()->getParams();
			if($pageForm->isValid($this->getRequest()->getParams())) {

				$pageData        = $pageForm->getValues();
				$pageData['url'] =  $this->_helper->page->validate($pageData['url']);
				//if we'r creating page -> check that we do not have an identical urls
				if(!$pageId) {
					$pageExists = $mapper->findByUrl($pageData['url']);
					if($pageExists instanceof Application_Model_Models_Page) {
						$this->_helper->response->fail('Page with url <strong>' . $this->_helper->page->validate($pageData['url']) . '</strong> already exists.');
						exit;
					}
					$checkFaPull = true;
				}

				//saving old data for seo routine
				$this->_helper->session->oldPageUrl = $page->getUrl();
				$this->_helper->session->oldPageH1  = $page->getH1();

				$page->registerObserver(new Tools_Seo_Watchdog());

				$page->setOptions($pageData);
				//prevent renaming index page
				if ($page->getUrl() != $this->_helper->website->getDefaultpage() ) {
					$page->setUrl($pageData['url']);
				}
				$page->setTargetedKey($page->getH1());

				//cleaning cache if it is a draft page
				if($pageData['pageCategory'] == Application_Model_Models_Page::IDCATEGORY_DRAFT) {
					$this->_helper->cache->clean(Helpers_Action_Cache::KEY_DRAFT, Helpers_Action_Cache::PREFIX_DRAFT);
				}

				$page->setParentId($pageData['pageCategory']);
				$page->setShowInMenu($pageData['inMenu']);
				$saveUpdateResult = $mapper->save($page);

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
			$this->_helper->response->fail(Tools_Content_Tools::proccessFormMessagesIntoHtml($pageForm->getMessages(), get_class($pageForm)));
			exit;
		}

		$this->view->faCount = ($page->getId()) ? sizeof(Application_Model_Mappers_FeaturedareaMapper::getInstance()->findAreasByPageId($page->getId())) : 0;

		//page preview image

		$this->view->pagePreviewImage = $this->_processPagePreviewImage($page->getUrl());
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

	public function deleteAction() {
		if($this->getRequest()->isPost()) {
			$pageMapper = Application_Model_Mappers_PageMapper::getInstance();
			$page       = $pageMapper->find(intval($this->getRequest()->getParam('id')));

			$page->registerObserver(new Tools_Page_GarbageCollector(array(
				'action' => Tools_System_GarbageCollector::CLEAN_ONDELETE
			)))->registerObserver(new Tools_Seo_Watchdog());

			$this->_helper->response->success($pageMapper->delete($page));
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
						Application_Model_Models_Page::IDCATEGORY_PRODUCT  => 'Product pages'
					),
					'Categories' => $categories
				);
			break;
			case Application_Model_Models_Page::IN_STATICMENU:
				$menuOptions= array(Application_Model_Models_Page::IDCATEGORY_DEFAULT => 'Make your selection');
			break;
			case Application_Model_Models_Page::IN_NOMENU:
				$menuOptions = array('-4' => 'Make your selection', 'No menu options' => array(
					Application_Model_Models_Page::IDCATEGORY_DEFAULT => 'This page is in no menu',
					Application_Model_Models_Page::IDCATEGORY_DRAFT   => 'This page is in draft'
				));
			break;
		}
		$selectHelper       = $this->view->getHelper('formSelect');

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
		//@todo can be added to the cache but not critical
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
					}
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
		$pageUrl            = $this->_helper->page->clean($pageUrl);
		$previewPath        = $websiteConfig['path'] .$websiteConfig['preview'];
		$filelist           = Tools_Filesystem_Tools::findFilesByExtension($previewPath, '(jpg|gif|png)', false, false, false);
		$currentPreviewList = preg_grep('/^'.$pageUrl.'\.(png|jpg|gif)$/', $filelist);

		if ($tmpPreviewFile) {
			$tmpPreviewFile = $websiteConfig['path'] . str_replace($this->_helper->website->getUrl(), '', $tmpPreviewFile);
			if (is_file($tmpPreviewFile) && is_readable($tmpPreviewFile)){
				preg_match('/\.[\w\d]{2,6}$/', $tmpPreviewFile, $extension);
				$newPreviewImageFile = $websiteConfig['path'].$websiteConfig['preview'].$pageUrl.$extension[0];

				//cleaning form existing page previews
				foreach ($currentPreviewList as $key => $file) {
					if(file_exists($previewPath . $file)) {
						if (Tools_Filesystem_Tools::deleteFile($previewPath.$file)){
							unset($currentPreviewList[0]);
						}
					}
				}

				if (is_writable($tmpPreviewFile)){
					$status = @rename($tmpPreviewFile, $newPreviewImageFile);
				} else {
					$status = @copy($tmpPreviewFile, $newPreviewImageFile);
				}
				if ($status && file_exists($tmpPreviewFile)) {

					Tools_Filesystem_Tools::deleteFile($tmpPreviewFile);
				}

				return $this->_helper->website->getUrl() . $websiteConfig['preview'] . $pageUrl.$extension[0];
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
		$pages = Application_Model_Mappers_PageMapper::getInstance()->fetchAll(null, array('h1 ASC'));
		$this->view->responseData = array_map(function($page) {
			return $page->toArray();
		}, $pages);
	}
}

