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
		$this->_uploadHandler->setDestination(realpath($this->_websiteConfig['path'] . $this->_websiteConfig['tmp']));
		
	}

	public function uploadAction() {
		$this->_helper->getHelper('layout')->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

		$this->_uploadHandler->clearFilters()->clearValidators();

		
		$methodName = '_upload'.ucfirst(strtolower($this->_caller));
		if (method_exists($this, $methodName)) {
			$response = $this->$methodName();
		} else {
			throw new Exceptions_SeotoasterException('Method not allowed.');
		}
		clearstatcache();
		//$this->_sendResponse($response);
		$this->_helper->json($response);
	}

	private function _sendResponse($response){
		if ($this->getRequest()->isXmlHttpRequest()){
			$this->getResponse()->setHeader('Content-type', 'application/json');
		} else {
			$this->getResponse()->setHeader('Content-type', 'text/plain');
		}
		$this->getResponse()
			->setHeader('Cache-Control', 'no-cache, must-revalidate')
			->setBody(json_encode($response))
			->sendResponse();
//		exit();
	}

	private function _uploadThemes(){
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
		$zip       = new ZipArchive();
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

	/**
	 * Handler for pictures/video upload interface
	 * @return array 
	 */
	private function _uploadImages() {
		$miscConfig = Zend_Registry::get('misc');
		$folder = $this->getRequest()->getParam('folder');
		if (!$folder || empty($folder)) {
			return array('error' => true, 'result' => 'No files uploaded. Please select folder.');
		}
		$folderValidator  = new Zend_Validate_Regex('~^[^\x00-\x1F"<>\|:\*\?/]+$~');
		if (!$folderValidator->isValid($folder)){
			return array('error' => true, 'result' => 'Bad folder name');
		}
		
		$savePath = $this->_websiteConfig['path'] . $this->_websiteConfig['images'] . $folder;
		if (!is_dir($savePath)){
			try{
				Tools_Filesystem_Tools::mkDir($savePath);
				$savePath = realpath($savePath);
			} catch (Exceptions_SeotoasterException $e){
				return array('error' => true, 'result' => $e->getMessage());
			}
		}
		
		$this->_uploadHandler->clearValidators()
			->addValidator('Extension', false,  array('jpg', 'png', 'gif'))
			->addValidator('MimeType', false, array('image/gif','image/jpeg','image/png'))
			->addValidator('IsImage', false)
			->addValidator('ImageSize', false, array('maxwidth' => $miscConfig['img_max_width'], 'maxheight' => $miscConfig['img_max_width']));
		
						
		if ($this->_uploadHandler->isUploaded() && $this->_uploadHandler->isValid()){
			if (!is_dir($savePath . DIRECTORY_SEPARATOR . 'original')){
				try{
					Tools_Filesystem_Tools::mkDir($savePath . DIRECTORY_SEPARATOR . 'original');
				} catch (Exceptions_SeotoasterException $e){

				}
			}
			$this->_uploadHandler->setDestination($savePath . DIRECTORY_SEPARATOR . 'original');
			$this->_uploadHandler->receive();
			$file = $this->_uploadHandler->getFileName();
			
			$status = Tools_Image_Tools::batchResize($file, $savePath);
							
			return array('error' => ($status !== true), 'result' => $status);
		}
		
		return array('error' => true, 'result' => $this->_uploadHandler->getMessages());
	}
	
	/**
 	 * Handler for files uploader
	 * @return array 
	 */
	public function _uploadFiles(){
		$this->_uploadHandler->clearValidators();
		$this->_uploadHandler->clearFilters();
		
		$savePath = $this->_websiteConfig['path'] . $this->_websiteConfig['downloads'];
			
		$file = reset($this->_uploadHandler->getFileInfo());
		$fileName = basename($this->_uploadHandler->getFileName());
		
		$nameValidator = new Zend_Validate_Regex('~^[^\x00-\x1F"<>\|:\*\?/]+\.[\w\d]{2,6}$~i');
		
		if (!$nameValidator->isValid($fileName)) {
			return array('result' => 'Corrupted filename' , 'error' => true);
		}
		
		$this->_uploadHandler->addFilter('Rename', array(
            'target' => $savePath . $fileName,
            'overwrite' => true
			));
		
		if ($this->_uploadHandler->isUploaded() && $this->_uploadHandler->isValid()){
			try {
				$this->_uploadHandler->receive();
			} catch (Exceptions_SeotoasterException $e){
				$response = array('result' => $e->getMessage(), 'error' => true);
			}
		}

		$response = array('result' => $this->_uploadHandler->getMessages() , 'error' => !$this->_uploadHandler->isReceived());
		
		return $response;
	}
}