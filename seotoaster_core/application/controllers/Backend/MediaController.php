<?php
/**
 * MediaController
 * Used for manipulation of image/files 
 * 
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Backend_MediaController extends Zend_Controller_Action {
	private $_translator	= null;
	private $_websiteConfig	= null;

	public function  init() {
		parent::init();
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_MEDIA)) {
			$this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
		}
		$this->view->websiteUrl = $this->_helper->website->getUrl();

		$this->_websiteConfig	= Zend_Registry::get('website');

		$this->_translator = Zend_Registry::get('Zend_Translate');
		
		$this->_helper->AjaxContext()->addActionContexts(array(
			'getdirectorycontent'	=> 'json',
			'removefile'			=> 'json'
			))->initContext('json');
	}

	/**
	 * Renders "Upload things" screen
	 */
	public function uploadthingsAction() {
		//creating list of folder in 'images' directory
		$listFolders = Tools_Filesystem_Tools::scanDirectoryForDirs($this->_websiteConfig['path'] . $this->_websiteConfig['media']);
		if (!empty ($listFolders)){
			$listFolders = array_combine($listFolders, $listFolders);
		}
		$this->view->listFolders = array_merge(array('select folder'), $listFolders);
	}

	/**
	 * Renders "Remove things" screen
	 */
	public function removethingsAction() {
		$listFolders = Tools_Filesystem_Tools::scanDirectoryForDirs($this->_websiteConfig['path'] . $this->_websiteConfig['media']);
		if (!empty ($listFolders)){
			$listFolders = array_combine($listFolders, $listFolders);
		}
		$this->view->listFolders = array_merge(array($this->_translator->translate('select folder')), $listFolders);
	}
	
	/** 
	 * Method for loading directory content via AJAX call
	 * @return JSON
	 */
	public function getdirectorycontentAction(){
		if ($this->getRequest()->isPost()){
			$folderName = $this->getRequest()->getParam('folder');
			$folderPath = realpath($this->_websiteConfig['path'].$this->_websiteConfig['media'].$folderName);
			//retrieve content for given folder
			if (!$folderName) {
				$this->view->error = 'No folder specified';
				return false;
			}
			$this->view->imageList = array();
			$listImages	= Tools_Filesystem_Tools::scanDirectory($folderPath.DIRECTORY_SEPARATOR.'small', false, false);
			foreach ($listImages as $image) {
				array_push($this->view->imageList, array(
					'name' => $image, 
					'src' => $this->_helper->website->getUrl().$this->_websiteConfig['media'].$folderName.DIRECTORY_SEPARATOR.'small'.DIRECTORY_SEPARATOR.$image
				));
			}
			$this->view->filesList = array();
			$listFiles	= Tools_Filesystem_Tools::scanDirectory($folderPath, false, false);
			foreach ($listFiles as $item){
				if (!is_dir($folderPath.DIRECTORY_SEPARATOR.$item)) {
					array_push($this->view->filesList, array('name' => $item));
				}
			}
		}
	}
	
	/** 
	 * Action used for removing images/files from media catalog
	 * for AJAX request
	 * @return JSON 
	 */
	public function removefileAction(){
		if ($this->getRequest()->isPost()) {
			var_dump($this->getRequest()->getParams());
			$folderName		= $this->getRequest()->getParam('folder');
			if (empty ($folderName)){
				$this->view->error = $this->_translator->translate('No folder specified');
				return false;
			}
			$removeImages	= $this->getRequest()->getParam('removeImages');
			$removeFiles	= $this->getRequest()->getParam('removeFiles');
			$errorList		= array();
			$folderPath		= realpath($this->_websiteConfig['path'].$this->_websiteConfig['media'].$folderName);
			
			if (!$folderPath || !is_dir($folderPath)){
				$this->view->error = $this->_translator->translate('No such folder');
				return false;
			}
			
			$containerMapper	= new Application_Model_Mappers_ContainerMapper();
			$pageMapper			= new Application_Model_Mappers_PageMapper();
			
			//processing images 
			if ( isset($removeImages) && is_array($removeImages) ){
				foreach ($removeImages as $imageName) {
					//checking if this image in any container
					$containers = $containerMapper->findByContent($imageName);
					if (!empty ($containers)){
						// formatting list of pages where image used in 
						$errorList[$imageName] = array();
						foreach ($containers as $container){
							$page = $pageMapper->find($container->getPageId());
							if (!in_array($page->getUrl(), $errorList[$imageName])){
								$errorList[$imageName][] = $page->getUrl();
							}
						}
					} else {
						// going to remove image
						try {
							$result = Tools_Image_Tools::removeImageFromFilesystem($imageName, $folderName);
							if ($result){
								$errorList[$imageName] = $result;
							}
						} catch (Exceptions_SeotoasterException $e) {
							error_log($e->getMessage().PHP_EOL.$e->getTraceAsString());
						}
					}
				}
			}
			
			//processing files
			if ( isset($removeFiles) && is_array($removeFiles)){
				foreach ($removeFiles as $file) {
					try {
						
					} catch (Exception $e) {
						error_log($e->getMessage().PHP_EOL.$e->getTraceAsString());
					}
								
				}
			}
			var_dump($errorList);
		}
	}
}