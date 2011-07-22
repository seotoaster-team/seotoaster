<?php
/**
 * MediaController
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
		//$this->_helper->AjaxContext()->addActionContext('removefiles', 'json')->initContext('json');
	}

	public function uploadthingsAction() {
		//creating list of folder in 'images' directory
		$listFolders = Tools_Filesystem_Tools::scanDirectoryForDirs($this->_websiteConfig['path'] . $this->_websiteConfig['media']);
		if (!empty ($listFolders)){
			$listFolders = array_combine($listFolders, $listFolders);
		}
		$this->view->listFolders = array_merge(array('select folder'), $listFolders);
	}

	public function removethingsAction() {
		$listFolders = Tools_Filesystem_Tools::scanDirectoryForDirs($this->_websiteConfig['path'] . $this->_websiteConfig['media']);
		$this->view->listFolders = array_merge(array($this->_translator->translate('select folder')), $listFolders);
	}
	
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
	
	public function removefileAction(){
		if ($this->getRequest()->isPost()) {
			$toRemove = $this->getRequest()->getParams('toremove');
			if (is_array($toRemove) && !empty ($toRemove)) {
				
			}
		}
	}
}