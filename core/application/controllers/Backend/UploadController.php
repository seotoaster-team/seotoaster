<?php
/**
 * UploadController - handler for upload form
 *
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 * @todo : response helper
 */
class Backend_UploadController extends Zend_Controller_Action {
    private $_caller = null;
	private $_uploadHandler = null;

	private $_websiteConfig;
	private $_themeConfig;

	public function init() {
		$this->_websiteConfig	= Zend_Registry::get('website');
		$this->_themeConfig		= Zend_Registry::get('theme');
		
		$this->_caller = $this->getRequest()->getParam('caller');
		$this->_uploadHandler = new Zend_File_Transfer_Adapter_Http();
		$this->_uploadHandler->setDestination(realpath($this->_websiteConfig['path'].$this->_websiteConfig['tmp']));
	}

	public function uploadAction() {
		$this->_helper->getHelper('layout')->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

		$this->_uploadHandler->clearFilters()->clearValidators();

		switch ($this->_caller) {
			case 'backend_theme#themes' :
				$response = $this->_uploadTheme();
				break;
			default :
				break;
		}
		clearstatcache();
		$this->_sendResponse($response);
	}

	private function _sendResponse($response){
		if ($this->getRequest()->isXmlHttpRequest()){
			$this->getResponse()->setHeader('Content-type', 'application/json');
		}
		$this->getResponse()
			->setHeader('Cache-Control', 'no-cache, must-revalidate')
			->setBody(json_encode($response))
			->sendResponse();
		exit();
	}

	private function _uploadTheme(){
		$this->_uploadHandler->addValidator('IsCompressed', false, array('application/zip'));
		$this->_uploadHandler->addValidator('Extension', false, 'zip');
		
		$themeArchive = $this->_uploadHandler->getFileInfo();

		if (!$this->_uploadHandler->isValid()){
			return 'error';
		}
		if (!extension_loaded('zip')){
			throw new Exceptions_SeotoasterException('No zip extension loaded');
		}
		$tmpFolder = $this->_uploadHandler->getDestination();
		$zip = new ZipArchive();
		$zip->open($themeArchive['file']['tmp_name']);
		$unzipped = $zip->extractTo($tmpFolder);
		if ($unzipped !== true){
			return array('name'=>$themeArchive['file']['name'], 'error' => 'Can\'t extract zip file to tmp directory');
		}
		$themeName = str_replace('.zip', '', $themeArchive['file']['name']);
		$isValid = $this->_validateTheme($themeName);
		if ( true === $isValid ) {
			$destinationDir = $this->_websiteConfig['path'].$this->_themeConfig['path'];
			if (is_dir($destinationDir.$themeName)){
				Tools_Filesystem_Tools::deleteDir($destinationDir.$themeName);
			}
			$zip->extractTo($destinationDir);
			$zip->close();
			Tools_Filesystem_Tools::deleteDir($tmpFolder.'/'.$themeName);
		} else {
			$zip->close();
			return array('name'=>$themeArchive['file']['name'], 'error' => $isValid);
		}
		return array(
			'error' => false,
			'name'=>$themeArchive['file']['name'],
			'type'=>$themeArchive['file']['type'],
			'size'=>$themeArchive['file']['size'],
			'themename' => $themeName
			);
	}

	/**
     * This function checks name of theme and returns array of errors.
     * @param type $themeFolder
     * @return mixed true if valid array with error description if not valid
     */
    private function _validateTheme($themename)
    {
		$tmpPath = $this->_uploadHandler->getDestination();
		$themeFolder = realpath($tmpPath.'/'.$themename);
		if ($themeFolder === false) {
			return 'Theme directory don\'t match the archive name.';
		}
        if (!is_dir($themeFolder)) {
            return 'Can not create folder for unpack zip file. 0peration not permitted.';
        }

        $listFiles = Tools_Filesystem_Tools::scanDirectory($themeFolder);
        if (empty($listFiles)) {
            return 'Your theme directory is empty.';
        }

        if (!preg_match("/^[a-zA-Z-0-9]{1,255}$/", $themename)) {
            return 'Theme name is invalid. Only letters, digits and dashes allowed.';
        }

        if (!in_array('style.css', $listFiles)) {
            return 'File "style.css" doesn\'t exits.';
        }

        if (!file_exists($themeFolder . '/index.html')) {
            return 'File "index.html" doesn\'t exits.';
        }

        if (!file_exists($themeFolder . '/category.html')) {
            return 'File "category.html" doesn\'t exits.';
        }

        if (!file_exists($themeFolder . '/default.html')) {
            return 'File "default.html" doesn\'t exits.';
        }

        if (!file_exists($themeFolder . '/news.html')) {
            return 'File "news.html" doesn\'t exits.';
        }

        if (!is_dir($themeFolder . '/images/')) {
            return 'Directory "images" doesn\'t exits.';
        }

        return true;
    }

}