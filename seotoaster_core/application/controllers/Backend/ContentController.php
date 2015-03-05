<?php
/**
 * ContentController
 *
 * @author Seotoaser Dev Team
 */
class Backend_ContentController extends Zend_Controller_Action {

    public static $_allowedActions = array('ajaxcontent');

	const IMG_CONTENTTYPE_SMALL    = 'small';

	const IMG_CONTENTTYPE_MEDIUM   = 'medium';

	const IMG_CONTENTTYPE_LARGE    = 'large';

	const IMG_CONTENTTYPE_ORIGINAL = 'original';

	private $_contentForm          = null;

	private $_containerType        = '';

	private $_websiteData          = array();

	public function init() {
		parent::init();
		$this->_websiteData = Zend_Registry::get('website');
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_CONTENT) && !Tools_Security_Acl::isActionAllowed(Tools_Security_Acl::RESOURCE_CONTENT)) {
			$this->redirect($this->_helper->website->getUrl(), array('exit' => true));
		}

		$this->_helper->viewRenderer->setNoRender(true);

		$this->_containerType     = $this->getRequest()->getParam('containerType');
		$this->_contentForm       = $this->_initCorrectForm();
		$this->view->websiteUrl   = $this->_helper->website->getUrl();
		$this->view->currentTheme = $this->_helper->config->getConfig('currentTheme');

        // content help section
        $this->view->helpSection  = 'content';

		$this->_helper->AjaxContext()->addActionContext('loadfiles', 'json')->initContext('json');
		$this->_helper->AjaxContext()->addActionContext('refreshfolders', 'json')->initContext('json');
        $this->_helper->AjaxContext()->addActionContext('cleancache', 'json')->initContext('json');
	}

	public function addAction() {
		if($this->getRequest()->isPost()) {
			$this->_processContent();
		}
		if ($this->getRequest()->isXmlHttpRequest()){
			$container = new Application_Model_Models_Container(array('containerType' => $this->_containerType));
			$this->_helper->json->direct($container->toArray());
		}
		$this->view->published      = true;
		$this->view->publishingDate = '';
		if($this->_containerType == Application_Model_Models_Container::TYPE_REGULARCONTENT || $this->_containerType == Application_Model_Models_Container::TYPE_STATICCONTENT) {
			$this->view->pluginsTabs = $this->_loadPluginsTabs();
		}
		echo $this->_renderCorrectView();
	}

	public function editAction() {
		if(!$this->getRequest()->isPost()) {
            $container = Application_Model_Mappers_ContainerMapper::getInstance();
            if ($this->getRequest()->getParam('id')) {
                $container = $container->find(
                    $this->getRequest()->getParam('id')
                );
            } else {
                $container = $container->findByName(
                    $this->getRequest()->getParam('name'), $this->getRequest()->getParam('pageId'), $this->getRequest()->getParam('containerType')
                );
            }
			if(null === $container) {
				throw new Exceptions_SeotoasterException('Container loading failed.');
			}
			if ($this->getRequest()->isXmlHttpRequest()){
				$this->_helper->json->direct($container->toArray());
			}
			$this->_contentForm->getElement('content')->setValue($container->getContent());
			$this->_contentForm->getElement('containerName')->setValue($container->getName());
			$this->_contentForm->getElement('containerId')->setValue($container->getId());
			$this->_contentForm->getElement('pageId')->setValue($container->getPageId());
			$this->_contentForm->getElement('containerType')->setValue($container->getContainerType());
			$this->_contentForm->setPublished($container->getPublished());

			$this->view->published      = $container->getPublished();
			$this->view->publishingDate = $container->getPublishingDate();

			if($container->getContainerType() == Application_Model_Models_Container::TYPE_REGULARCONTENT || $container->getContainerType() == Application_Model_Models_Container::TYPE_STATICCONTENT) {
				$this->view->pluginsTabs = $this->_loadPluginsTabs();
			}
		}
		else {
			$this->_processContent();
		}
		echo $this->_renderCorrectView();
	}

    public function ajaxcontentAction() {
        $currentPage =  Application_Model_Mappers_PageMapper::getInstance()->find($this->getRequest()->getParam('pageId'));
        $currentPage = ($currentPage == null) ? array() : $currentPage->toArray();
        $parseContent = new Tools_Content_Parser('{$' . $this->getRequest()->getParam('widget') . '}', $currentPage, array('websiteUrl'   => $this->_helper->website->getUrl()));
        $this->_helper->response->success($parseContent->parseSimple());
    }

	private function _loadPluginsTabs() {
		if(!($pluginsTabsData = $this->_helper->cache->load(Helpers_Action_Cache::KEY_PLUGINTABS, Helpers_Action_Cache::PREFIX_PLUGINTABS))) {
			$pluginsTabsData  = Tools_Plugins_Tools::getPluginTabContent();
			$this->_helper->cache->save(Helpers_Action_Cache::KEY_PLUGINTABS, $pluginsTabsData, Helpers_Action_Cache::PREFIX_PLUGINTABS, array(), Helpers_Action_Cache::CACHE_LONG);
		}
		return $pluginsTabsData;
	}

	private function _processContent() {
        $this->_contentForm = Tools_System_Tools::addTokenValidatorZendForm($this->_contentForm, Tools_System_Tools::ACTION_PREFIX_CONTAINERS);
        if($this->_contentForm->isValid($this->getRequest()->getParams())) {
			$containerData = $this->_contentForm->getValues();
			$pageId        = ($containerData['containerType'] == Application_Model_Models_Container::TYPE_STATICCONTENT || $containerData['containerType'] == Application_Model_Models_Container::TYPE_STATICHEADER || $containerData['containerType'] == Application_Model_Models_Container::TYPE_PREPOPSTATIC) ? null : $containerData['pageId'];
			$containerId   = ($containerData['containerId']) ? $containerData['containerId'] : null;
			$container     = new Application_Model_Models_Container();

			$container->registerObserver(new Tools_Seo_Watchdog());
			$container->registerObserver(new Tools_Search_Watchdog());
			$container->registerObserver(new Tools_Content_GarbageCollector(array(
				'action' => Tools_System_GarbageCollector::CLEAN_ONUPDATE
			)));

			$container->setId($containerId)
				->setName($containerData['containerName'])
				->setContainerType($containerData['containerType'])
				->setPageId($pageId)
				->setContent($containerData['content']);
			$published = ($container->getContainerType() == Application_Model_Models_Container::TYPE_REGULARCONTENT || $container->getContainerType() == Application_Model_Models_Container::TYPE_STATICCONTENT) ? $this->getRequest()->getParam('published') : true;
			$container->setPublished($published);
			if(!$published) {
				$publishOn = $this->getRequest()->getParam('publishOn');
				if($publishOn) {
					$container->setPublishingDate($publishOn);
				}
			}
			else {
				$container->setPublishingDate('');
			}

            $cacheTag = preg_replace('/[^\w\d_]/', '', $container->getName() . '_' . $container->getContainerType() . '_pid_' . $container->getPageId());
			$this->_helper->cache->clean(null, null, array($cacheTag));
			$saveResult = Application_Model_Mappers_ContainerMapper::getInstance()->save($container);

			if(!$container->getId()) {
				$container->setId($saveResult);
			}

			try {
				$container->notifyObservers();
			} catch(Exceptions_SeotoasterWidgetException $twe) {
				$this->_helper->response->fail($twe->getMessage());
			}
			$this->_helper->response->success($saveResult);
			exit;
		}
		return false;
	}

	private function _renderCorrectView() {
        $secureToken = Tools_System_Tools::initZendFormCsrfToken($this->_contentForm, Tools_System_Tools::ACTION_PREFIX_CONTAINERS);
        $this->view->secureToken = $secureToken;
        $this->view->contentForm = $this->_contentForm;
		$rendered = '';
		switch ($this->_containerType) {
			case Application_Model_Models_Container::TYPE_REGULARCONTENT:
			case Application_Model_Models_Container::TYPE_STATICCONTENT:
				$this->view->imagesSizes = array(
					'small'  => $this->_helper->config->getConfig('imgSmall'),
					'medium' => $this->_helper->config->getConfig('imgMedium'),
					'large'  => $this->_helper->config->getConfig('imgLarge')
				);
                $this->view->linkResetCss     = Tools_Theme_Tools::urlResetCss();
                $this->view->linkContentCss     = Tools_Theme_Tools::urlContentCss();
				$this->view->pluginsEditorLinks = $this->_loadPluginsEditorLinks();
				$this->view->pluginsEditorTop   = $this->_loadPluginsEditorTop();
				$rendered                       = $this->view->render('backend/content/content.phtml');
			break;
			case Application_Model_Models_Container::TYPE_REGULARHEADER:
			case Application_Model_Models_Container::TYPE_STATICHEADER:
				$rendered = $this->view->render('backend/content/header.phtml');
			break;
			case Application_Model_Models_Container::TYPE_CODE:
				$rendered = $this->view->render('backend/content/code.phtml');
			break;
		}
		return $rendered;
	}

	private function _loadPluginsEditorLinks() {
		if(!($pluginsEditorLinks = $this->_helper->cache->load(Helpers_Action_Cache::KEY_PLUGINEDITOR_LINKS, Helpers_Action_Cache::PREFIX_PLUGINEDITOR_LINKS))) {
			$pluginsEditorLinks  = Tools_Plugins_Tools::getPluginEditorLink();
			$this->_helper->cache->save(Helpers_Action_Cache::KEY_PLUGINEDITOR_LINKS, $pluginsEditorLinks, Helpers_Action_Cache::PREFIX_PLUGINEDITOR_LINKS, array(), Helpers_Action_Cache::CACHE_LONG);
		}
		return $pluginsEditorLinks;
	}

	private function _loadPluginsEditorTop() {
		if(!($pluginsEditorTop = $this->_helper->cache->load(Helpers_Action_Cache::KEY_PLUGINEDITOR_TOP, Helpers_Action_Cache::PREFIX_PLUGINEDITOR_TOP))) {
			$pluginsEditorTop  = Tools_Plugins_Tools::getPluginEditorTop();
			$this->_helper->cache->save(Helpers_Action_Cache::KEY_PLUGINEDITOR_LINKS, $pluginsEditorTop, Helpers_Action_Cache::PREFIX_PLUGINEDITOR_TOP, array(), Helpers_Action_Cache::CACHE_LONG);
		}
		return $pluginsEditorTop;
	}

	private function _initCorrectForm() {
		$form = null;
		switch ($this->_containerType) {
			case Application_Model_Models_Container::TYPE_REGULARCONTENT:
			case Application_Model_Models_Container::TYPE_STATICCONTENT:
				$this->_initContentToolbar();
				$form = new Application_Form_Content();
			break;
			case Application_Model_Models_Container::TYPE_REGULARHEADER:
			case Application_Model_Models_Container::TYPE_STATICHEADER:
				$form = new Application_Form_Header();
			break;
			case Application_Model_Models_Container::TYPE_CODE:
				$form = new Application_Form_Code();
			break;
            case Application_Model_Models_Container::TYPE_PREPOP:
            case Application_Model_Models_Container::TYPE_PREPOPSTATIC:
                $form = new Application_Form_Prepop();
            break;
		}
		return $form;
	}

	private function _initContentToolbar() {
		$websiteData      = Zend_Registry::get('website');
		$imgDirectoryPath = $websiteData['path'] . $websiteData['media'];
		try {
			$this->view->imageFolders = Tools_Filesystem_Tools::scanDirectoryForDirs($imgDirectoryPath);
		}
		catch (Exceptions_SeotoasterException $ste) {
			return array();
		}
	}

	/**
	 * Ajax hendler
	 * Called in tinymceInit.js
	 */
	public function loadwidgetsAction() {
		$this->_helper->getHelper('layout')->disableLayout();
		if($this->getRequest()->isPost()) {
			if(!($widgetsData = $this->_helper->cache->load('widgetsData', 'wd_'))) {
				$widgetsData = Tools_Widgets_Tools::getAllowedOptions();
				$this->_helper->cache->save('widgetsData', $widgetsData, 'wd_', array(), Helpers_Action_Cache::CACHE_LONG);
			}
			$this->getResponse()->setBody(json_encode($widgetsData))->sendResponse();
		}
		exit;
	}

	public function loadimagesAction() {
		//@todo add images to the cache?
		$this->_helper->getHelper('layout')->disableLayout();

		if($this->getRequest()->isPost()) {
			$imagesData  = array();
			$folderName  = $this->getRequest()->getParam('folderName');
			$imagesPath  = $this->_websiteData['path'] . $this->_websiteData['media'] . $folderName;
			try {
                $imagesData  = array(
                    'small'    => '<div class="images-preview list-images">' . $this->_proccessImages(Tools_Filesystem_Tools::scanDirectory($imagesPath . '/' . self::IMG_CONTENTTYPE_SMALL), $imagesPath, $folderName, self::IMG_CONTENTTYPE_SMALL) . '</div>',
                    'medium'   => '<div class="images-preview list-images">' . $this->_proccessImages(Tools_Filesystem_Tools::scanDirectory($imagesPath . '/' . self::IMG_CONTENTTYPE_MEDIUM), $imagesPath, $folderName, self::IMG_CONTENTTYPE_MEDIUM) . '</div>',
                    'large'    => '<div class="images-preview list-images">' . $this->_proccessImages(Tools_Filesystem_Tools::scanDirectory($imagesPath . '/' . self::IMG_CONTENTTYPE_LARGE), $imagesPath, $folderName, self::IMG_CONTENTTYPE_LARGE) . '</div>',
                    'original' => '<div class="images-preview list-images">' . $this->_proccessImages(Tools_Filesystem_Tools::scanDirectory($imagesPath . '/' . self::IMG_CONTENTTYPE_ORIGINAL), $imagesPath, $folderName, self::IMG_CONTENTTYPE_ORIGINAL) . '</div>'
                );
            }
            catch(Exceptions_SeotoasterException $se) {
                $imagesData = array(
                    'small'    => $this->_helper->language->translate('No images were found'),
                    'medium'   => $this->_helper->language->translate('No images were found'),
                    'large'    => $this->_helper->language->translate('No images were found'),
                    'original' => $this->_helper->language->translate('No images were found')
                );
            }
			$this->getResponse()->setBody(json_encode($imagesData))->sendResponse();
		}
		exit;
	}

	public function loadfilesAction() {
		if($this->getRequest()->isPost()) {
			$folder             = $this->getRequest()->getParam('folder');
			$filesPath          = $this->_websiteData['path'] . $this->_websiteData['media'] . $folder;
			$this->view->files  = ((is_dir($filesPath))) ? Tools_Filesystem_Tools::findFilesByExtension($filesPath, '.*', false, false, false) : array();
			$this->view->html   = (($folder) ? $this->view->render('backend/content/files.phtml') : '<h3 class="text-center mt10px">' . $this->_helper->language->translate('Please, select a folder') . '</h3>');
		}
	}

	private function _proccessImages(array $images, $path, $folder, $type) {
		if(!empty ($images)) {
			$imagesContent = '';
			$srcPath = $this->_helper->website->getUrl() . $this->_helper->website->getMedia() . $folder;
			foreach ($images as $key => $image) {
                $srcPath        = Tools_Content_Tools::applyMediaServers($srcPath);
	            $imageName      = preg_replace('~\.(jpg|png|gif|jpeg)~i', '', $image);
				$imageSize      = getimagesize($path . '/' . $type . '/' . $image);
				$imageElement   = htmlspecialchars('<a class="_lbox" href="' . $srcPath . '/' .  self::IMG_CONTENTTYPE_ORIGINAL . '/' . $image . '" title="' . str_replace('-', '&nbsp;', $imageName) . '"><img border="0" alt="'. str_replace('-', '&nbsp;', $imageName) . '" src="' . $srcPath . '/' . $type . '/' . $image . '" width="' . $imageSize[0] . '" height="' . $imageSize[1] . '" /></a>');
				$imagesContent .= '<a href="javascript:;" onmousedown="tinymce.activeEditor.execCommand(\'mceInsertContent\', false, \'' . $imageElement . '\');">';
				$imagesContent .= '<img title="' . $image . '" border="0" width="80" src="' . $srcPath . '/product/' . $image .'" /></a>';
			}
			return $imagesContent;
		}
	}

	public function loadwidgetmakerAction() {
		$this->_helper->getHelper('layout')->disableLayout();
		if($this->getRequest()->isPost() || $this->getRequest()->isGet()) {
			if(!($widgetMakerContent = $this->_helper->cache->load('widgetMakerContent', 'wmc_'))) {
				$this->view->widgetsData = array_merge(Tools_Widgets_Tools::getWidgetmakerContent(), Tools_Plugins_Tools::getWidgetmakerContent());
				$widgetMakerContent      = $this->view->render('backend/content/widgetmaker.phtml');
				$this->_helper->cache->save('widgetMakerContent', $widgetMakerContent, 'wmc_', array(), Helpers_Action_Cache::CACHE_LONG);
			}
			//$this->_helper->response->success($widgetMakerContent);
			echo $widgetMakerContent;
		}
		exit;
	}

	public function refreshfoldersAction() {
		$websiteData = Zend_Registry::get('website');
		$this->_helper->response->success(Tools_Filesystem_Tools::scanDirectoryForDirs($websiteData['path'] . $websiteData['media']));
	}

    /**
     * Clear all cache
     * Called in adminPanelInit.min.js
     */
    public function cleancacheAction() {
        if (Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_CONTENT)) {
            try {
                $this->_helper->cache->clean();
                $this->_helper->response->success(
                    $this->_helper->language->translate('The entire cache has been cleaned.')
                );
            }
            catch (Exceptions_SeotoasterException $ste) {
                $this->_helper->response->fail($ste->getMessage());
            }
        }
    }

    public function editrepeatAction()
    {
        $configRepeat = new Application_Form_Repeat();
        $configRepeat->setAction($this->_helper->url->url());

        $mapper = Application_Model_Mappers_ContainerMapper::getInstance();
        $model  = new Application_Model_Models_Container();
        $name   = MagicSpaces_Repeat_Repeat::PREFIX_CONTAINER.$this->getRequest()->getParam('repeatName');
        $type   = $this->getRequest()->getParam('contentType');
        $pageId = (Application_Model_Models_Container::TYPE_REGULARCONTENT == $type)
            ? $this->getRequest()->getParam('pageId')
            : null;
        $data   = $mapper->findByName($name, $pageId, $type);
        if ($data instanceof Application_Model_Models_Container) {
            $model->setId($data->getId());

            $content = explode(':', $data->getContent());
            if (isset($content[0], $content[1], $content[2])) {
                $configRepeat->setQuantity($content[0])->setOrderContent($content[1])->setInversion($content[2]);
            }
        }


        if ($this->getRequest()->isPost()) {
            $tokenToValidate = $this->getRequest()->getParam(Tools_System_Tools::CSRF_SECURE_TOKEN, false);
            $valid = Tools_System_Tools::validateToken($tokenToValidate, Tools_System_Tools::ACTION_PREFIX_EDITREPEAT);
            if (!$valid) {
                $this->_helper->response->fail('');
            }
            $quantity     = filter_var($this->getRequest()->getParam('quantity'), FILTER_SANITIZE_NUMBER_INT);
            $orderContent = $this->getRequest()->getParam('orderContent');
            $inversion    = $this->getRequest()->getParam('inversion');
            $model->setName($name)->setContainerType($type)->setPageId($pageId);
            // Delete
            if (empty($quantity) && empty($orderContent) && empty($inversion)) {
                $configRepeat->setQuantity(null)->setOrderContent(null)->setInversion(null);

                if ($data instanceof Application_Model_Models_Container) {
                    $mapper->delete($model);
                }
            }
            // Save
            else {
                $configRepeat->setQuantity($quantity)->setOrderContent($orderContent)->setInversion($inversion);

                $model->setContent(
                    $configRepeat->getQuantity().':'.$configRepeat->getOrderContent().':'.$configRepeat->getInversion()
                );
                $mapper->save($model);
            }
        }
        $secureToken = Tools_System_Tools::initSecureToken(Tools_System_Tools::ACTION_PREFIX_EDITREPEAT);
        $this->view->secureToken = $secureToken;
        $this->view->configRepeat = $configRepeat;

        echo $this->view->render('backend/magicspaces/repeat.phtml');
    }
}
