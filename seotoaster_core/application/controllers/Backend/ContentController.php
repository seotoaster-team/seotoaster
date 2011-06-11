<?php
/**
 * ContentController
 *
 * @author Seotoaser Dev Team
 */
class Backend_ContentController extends Zend_Controller_Action {

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
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_CONTENT)) {
			$this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
		}

		$this->_helper->viewRenderer->setNoRender(true);

		$this->_containerType     = $this->getRequest()->getParam('containerType');
		$this->_contentForm       = $this->_initCorrectForm();
		$this->view->websiteUrl   = $this->_helper->website->getUrl();
		$this->view->currentTheme = $this->_helper->config->getConfig('current_theme');
	}

	public function addAction() {
		if($this->getRequest()->isPost()) {
			$this->_processContent();
		}
		$this->view->published      = true;
		$this->view->publishingDate = '';
		echo $this->_renderCorrectView();
	}

	public function editAction() {
		if(!$this->getRequest()->isPost()) {
			$mapper = new Application_Model_Mappers_ContainerMapper();
			$container = $mapper->find($this->getRequest()->getParam('id'));
			if(null === $container) {
				throw new Exceptions_SeotoasterException('Container loading failed.');
			}
			$this->_contentForm->getElement('content')->setValue($container->getContent());
			$this->_contentForm->getElement('containerName')->setValue($container->getName());
			$this->_contentForm->getElement('containerId')->setValue($container->getId());
			$this->_contentForm->getElement('pageId')->setValue($container->getPageId());
			$this->_contentForm->getElement('containerType')->setValue($container->getContainerType());
			$this->_contentForm->setPublished($container->getPublished());

			$this->view->published      = $container->getPublished();
			$this->view->publishingDate = $container->getPublishingDate();
		}
		else {
			$this->_processContent();
		}
		echo $this->_renderCorrectView();
	}

	private function _processContent() {
		if($this->_contentForm->isValid($this->getRequest()->getParams())) {
			$containerData = $this->_contentForm->getValues();
			$pageId        = ($containerData['containerType'] == Application_Model_Models_Container::TYPE_STATICCONTENT || $containerData['containerType'] == Application_Model_Models_Container::TYPE_STATICHEADER) ? 0 : $containerData['pageId'];
			$containerId   = ($containerData['containerId']) ? $containerData['containerId'] : null;
			$container     = new Application_Model_Models_Container();

			$container->registerObserver(new Tools_Content_GarbageCollector());
			$container->registerObserver(new Tools_Seo_Generator());

			$container->setId($containerData['containerId'])
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
			$mapper = new Application_Model_Mappers_ContainerMapper();
			$this->_helper->cache->clean($container->getName() . $pageId, 'widget_');
			$this->getResponse()->setHttpResponseCode(200);

			$saveResult = $mapper->save($container);
			if(!$container->getId()) {
				$container->setId($saveResult);
			}

			$this->getResponse()->setBody($saveResult);
			$this->getResponse()->sendResponse();

			$container->notifyObservers();

			exit;
		}
		return false;
	}

	private function _renderCorrectView() {
		$this->view->contentForm = $this->_contentForm;
		$rendered = '';
		switch ($this->_containerType) {
			case Application_Model_Models_Container::TYPE_REGULARCONTENT:
			case Application_Model_Models_Container::TYPE_STATICCONTENT:
				$rendered = $this->view->render('backend/content/content.phtml');
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
		}
		return $form;
	}

	private function _initContentToolbar() {
		$websiteData      = Zend_Registry::get('website');
		$imgDirectoryPath = $websiteData['path'] . $websiteData['images'];
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
			$imagesPath  = $this->_websiteData['path'] . $this->_websiteData['images'] . $folderName;
			$imagesData  = array(
				'small'    => $this->_proccessImages(Tools_Filesystem_Tools::scanDirectory($imagesPath . '/' . self::IMG_CONTENTTYPE_SMALL), $imagesPath, $folderName, self::IMG_CONTENTTYPE_SMALL),
				'medium'   => $this->_proccessImages(Tools_Filesystem_Tools::scanDirectory($imagesPath . '/' . self::IMG_CONTENTTYPE_MEDIUM), $imagesPath, $folderName, self::IMG_CONTENTTYPE_MEDIUM),
				'large'    => $this->_proccessImages(Tools_Filesystem_Tools::scanDirectory($imagesPath . '/' . self::IMG_CONTENTTYPE_LARGE), $imagesPath, $folderName, self::IMG_CONTENTTYPE_LARGE),
				'original' => $this->_proccessImages(Tools_Filesystem_Tools::scanDirectory($imagesPath . '/' . self::IMG_CONTENTTYPE_ORIGINAL), $imagesPath, $folderName, self::IMG_CONTENTTYPE_ORIGINAL)
			);
			$this->getResponse()->setBody(json_encode($imagesData))->sendResponse();
		}
		exit;
	}

	public function loadfilesAction() {
		$this->_helper->getHelper('layout')->disableLayout();
		if($this->getRequest()->isPost()) {
			$filesPath = $this->_websiteData['path'] . $this->_websiteData['downloads'];
			$this->view->files = Tools_Filesystem_Tools::scanDirectory($filesPath);
			$this->_helper->response->success($this->view->render('backend/content/files.phtml'));
		}
		exit;
	}

	private function _proccessImages(array $images, $path, $folder, $type) {
		if(!empty ($images)) {
			$imagesContent = '';
			$srcPath       = $this->_websiteData['url'] . $this->_websiteData['images'] . $folder;
			foreach ($images as $key => $image) {
				$imageSize      = getimagesize($path . '/' . $type . '/' . $image);
				$imageElement   = '<a href=' . $srcPath . '/' .  self::IMG_CONTENTTYPE_ORIGINAL . '/' . $image . ' title=' . str_replace('-', '&nbsp;', $image) . ' class=thickbox><img border=0 alt='. str_replace('-', '&nbsp;',$image) . ' src=' . $srcPath . '/' . $type . '/' . $image . ' width=' . $imageSize[0] . ' height=' . $imageSize[1] . ' /></a>';
				$imagesContent .= '<a href="javascript:;" onmousedown="$(\'#content\').tinymce().execCommand(\'mceInsertContent\', false, \'' . $imageElement . '\');">';
				$imagesContent .= '<img title="' . $image . '" style="vertical-align:top; margin: 0px 0px 4px 4px;" border="0" width="65" src="' . $srcPath . '/'. $type . '/' . $image .'" /></a>';
			}
			return $imagesContent;
		}
	}

	public function loadwidgetmakerAction() {
		$this->_helper->getHelper('layout')->disableLayout();
		if($this->getRequest()->isPost()) {
			if(!($widgetMakerContent = $this->_helper->cache->load('widgetMakerContent', 'wmc_'))) {
				$this->view->widgetsData = array_merge(Tools_Widgets_Tools::getWidgetmakerContent(), Tools_Plugins_Tools::getWidgetmakerContent());
				$widgetMakerContent      = $this->view->render('backend/content/widgetmaker.phtml');
				$this->_helper->cache->save('widgetMakerContent', $widgetMakerContent, 'wmc_', array(), Helpers_Action_Cache::CACHE_LONG);
			}
			$this->_helper->response->success($widgetMakerContent);
		}
		exit;
	}
}

