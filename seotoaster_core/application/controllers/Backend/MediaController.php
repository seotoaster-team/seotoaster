<?php
/**
 * MediaController
 *
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Backend_MediaController extends Zend_Controller_Action {
    
	public function  init() {
		parent::init();
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_MEDIA)) {
			$this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
		}
		$this->view->websiteUrl = $this->_helper->website->getUrl();
		
		$this->_websiteConfig	= Zend_Registry::get('website');
		
		$this->_translator = Zend_Registry::get('Zend_Translate');
	}
	
	public function uploadthingsAction() {
		//creating list of folder in 'images' directory
		$listFolders = Tools_Filesystem_Tools::scanDirectoryForDirs($this->_websiteConfig['path'].$this->_websiteConfig['media']);
		if (!empty ($listFolders)){
		$listFolders = array_combine($listFolders, $listFolders);
		}
		$this->view->listFolders = array_merge(array('select folder'), $listFolders);
	}

	public function removethingsAction() {

	}
}