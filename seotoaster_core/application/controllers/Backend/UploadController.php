<?php

/**
 * UploadController - handler for upload form
 *
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 * @todo   : response helper
 */
class Backend_UploadController extends Zend_Controller_Action
{
    private $_caller             = null;

    /**
     * @var Zend_File_Transfer_Adapter_Http
     */
    private $_uploadHandler      = null;

    private $_websiteConfig;
    private $_themeConfig;

    /**
     * @var bool Flag to check mime or extension of uploaded file
     */
    private $_checkMime          = true;

    /**
     * @var Zend_Translate
     */
    protected $_translator;

    const PREVIEW_IMAGE_OPTIMIZE = '85';


    public function init()
    {
        parent::init();
        if (!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_MEDIA)) {
            $this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
        }
        $this->_websiteConfig = Zend_Registry::get('website');
        $this->_themeConfig = Zend_Registry::get('theme');
        $this->_translator = Zend_Registry::get('Zend_Translate');

        $this->_caller = $this->getRequest()->getParam('caller');
        $this->_uploadHandler = new Zend_File_Transfer_Adapter_Http();
//		$this->_uploadHandler->setDestination(realpath($this->_websiteConfig['path'] . $this->_websiteConfig['tmp']));
        if (!extension_loaded('fileinfo')) {
            $this->_checkMime = false;
        }
    }

    public function uploadAction()
    {
        $this->_helper->getHelper('layout')->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $this->_uploadHandler->clearFilters()->clearValidators();

        $methodName = '_upload' . ucfirst(strtolower($this->_caller));
        if (method_exists($this, $methodName)) {
            $response = $this->$methodName();
        } else {
            throw new Exceptions_SeotoasterException('Method not allowed.');
        }
        clearstatcache();
        //$this->_sendResponse($response);
        $this->_helper->json($response);
    }

    /**
     * Send response to client
     * deprecated since became using JSON helper
     * @param type $response
     * @deprecated
     * @todo remove before release
     */
    private function _sendResponse($response)
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
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

    private function _uploadThemes()
    {
        $this->_uploadHandler->addValidator('Extension', false, 'zip');

        if ($this->_checkMime) {
            $this->_uploadHandler->addValidator(new Validators_MimeType(array('application/zip')), false);
        }

        $themeArchive = $this->_uploadHandler->getFileInfo();
        if (!$this->_uploadHandler->isValid()) {
            return array(
                'name' => $themeArchive['file']['name'],
                'error' => 'Uploaded file is not a valid zip archive'
            );
        }
        if (!extension_loaded('zip')) {
            throw new Exceptions_SeotoasterException('No zip extension loaded');
        }

        $tmpFolder = $this->_websiteConfig['path'].$this->_websiteConfig['tmp'];
        $zip       = new ZipArchive();
        $res       = $zip->open($themeArchive['file']['tmp_name']);
        if ($res !== true) {
            return array('name' => $themeArchive['file']['name'], 'error' => 'Can\'t open zip file');
        }

        $themeName      = str_replace('.zip', '', $themeArchive['file']['name']);
        $themeName      = trim(preg_replace('/\s+/', '_', $themeName), '_');
        $destinationDir = $this->_websiteConfig['path'].$this->_themeConfig['path'].$themeName;
        $isValid        = $this->_validateTheme($zip);
        if (empty($isValid['error'])) {
            $zip = $isValid['zip'];
            try {
                if (is_dir($destinationDir)) {
                    Tools_Filesystem_Tools::deleteDir($destinationDir);
                }
                $unzipped = $zip->extractTo($destinationDir);
                if ($unzipped !== true) {
                    $status = array(
                        'name'  => $themeArchive['file']['name'],
                        'error' => 'Can\'t extract zip file to tmp directory');
                }
            }
            catch (Exception $e) {
                error_log($e->getMessage());
            }
        }
        else {
            $status = array('name' => $themeArchive['file']['name'], 'error' => $isValid['error']);
        }

        $zip->close();
        if (file_exists($tmpFolder.DIRECTORY_SEPARATOR.$themeArchive['file']['name'])) {
            Tools_Filesystem_Tools::deleteFile($tmpFolder.DIRECTORY_SEPARATOR.$themeArchive['file']['name']);
        }

        return isset($status) ? $status : array(
            'error'     => false,
            'name'      => $themeArchive['file']['name'],
            'type'      => $themeArchive['file']['type'],
            'size'      => $themeArchive['file']['size'],
            'themename' => $themeName
        );
    }

    /**
     * This function checks name of theme and returns array of errors.
     * @param type $themeFolder
     * @return mixed true if valid array with error description if not valid
     */
    private function _validateTheme($zip)
    {
        $data = array(
            'zip'   => $zip,
            'error' => array(),
        );

        $themeContent            = array();
        $errorDirectorySeparator = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $file     = $zip->statIndex($i);
            $fileName = $file['name'];

            // Discrepancy system directory separator
            if (strpos($fileName, '\\') && DIRECTORY_SEPARATOR !== '\\') {
                $errorDirectorySeparator = true;
                $fileName                = str_replace('\\', DIRECTORY_SEPARATOR, $fileName);

                $zip->renameIndex($i, $fileName);
            }
            elseif (strpos($fileName, '/') && DIRECTORY_SEPARATOR !== '/') {
                $errorDirectorySeparator = true;
                $fileName                = str_replace('/', DIRECTORY_SEPARATOR, $fileName);

                $zip->renameIndex($i, $fileName);
            }

            $themeContent[] = $fileName;
        }

        if (!is_array($themeContent) || empty($themeContent)) {
            array_push($data['error'], $this->view->translate('Your theme directory is empty.'));
        }

        // Set updated data, if $errorDirectorySeparator = true
        if ($errorDirectorySeparator) {
            $zip->close();

            $themeArchive = $this->_uploadHandler->getFileInfo();
            if (($res = $zip->open($themeArchive['file']['tmp_name'])) !== true) {
                array_push($data['error'], $this->view->translate('Can\'t open zip file.'));

                return $data;
            }

            $data['zip'] = $zip;
        }

        // Checed required files
        foreach (Tools_Theme_Tools::$requiredFiles as $file) {
            if ('css' == pathinfo($file, PATHINFO_EXTENSION)) {
                if (in_array($file, $themeContent)) {
                    continue;
                }
            if (in_array(Tools_Theme_Tools::FOLDER_CSS .DIRECTORY_SEPARATOR. $file, $themeContent)) {
                    continue;
                }

                array_push($data['error'], $this->view->translate("File %s doesn't exists.", $file));
            }
            elseif (!in_array($file, $themeContent)) {
                array_push($data['error'], $this->view->translate("File %s doesn't exists.", $file));
            }
        }

        return $data;
    }

    /**
     * Handler for pictures/video upload interface
     * @return array
     */
    private function _uploadImages($savePath = null, $resize = true)
    {
        $miscConfig = Zend_Registry::get('misc');

        if (!$savePath) {
            //useful if file submited directly to this method
            $savePath = $this->_getSavePath();
        }

        $this->_uploadHandler->clearValidators()
            ->addValidator('Extension', false, array('jpeg', 'jpg', 'png', 'gif'))
            ->addValidator('ImageSize', false, array('maxwidth' => $miscConfig['imgMaxWidth'], 'maxheight' => $miscConfig['imgMaxWidth']));

        if ($this->_checkMime) {
            $this->_uploadHandler->addValidator(new Validators_MimeType(array('image/gif', 'image/jpeg', 'image/jpg', 'image/png')), false);
        }

        $receivePath = ($resize ? $savePath . DIRECTORY_SEPARATOR . 'original' : $savePath);

        if ($this->_uploadHandler->isUploaded() && $this->_uploadHandler->isValid()) {
            if (!is_dir($receivePath)) {
                try {
                    Tools_Filesystem_Tools::mkDir($receivePath);
                } catch (Exceptions_SeotoasterException $e) {
                    error_log($e->getMessage());
                }
            }
            if (!$this->_uploadHandler->hasFilter('Rename')) {
                /**
                 * Renaming file if additional field 'name' was submited with file
                 */
                $filterChain = new Zend_Filter();
                $filterChain->addFilter(new Zend_Filter_StringTrim())
                    ->addFilter(new Zend_Filter_StringToLower('UTF-8'))
                    ->addFilter(new Zend_Filter_PregReplace(array('match' => '/[^\w\d_]+/u', 'replace' => '-')));

                // filtering the img name
                //$expFileName = explode('.', $this->_uploadHandler->getFileName(null, false));
                $expFileName = explode('.', $this->getRequest()->getParam('name', false));
                $fileExt = array_pop($expFileName);
                if (!in_array($fileExt, array('jpeg', 'jpg', 'png', 'gif'))) {
                    return array('error' => true, 'result' => "Wrong file extension");
                }
                $name = implode($expFileName);
                $newName = $filterChain->filter($name) . '.' . $fileExt;

                if (false !== ($newName)) {
                    $this->_uploadHandler->addFilter('Rename', array(
                        'target' => $receivePath . DIRECTORY_SEPARATOR . $newName,
                        'overwrite' => true
                    ));
                } else {
                    $this->_uploadHandler->addFilter('Rename', array(
                        'target' => $receivePath,
                        'overwite' => true
                    ));
                }
            }
            if ($this->_uploadHandler->receive()) {
                $fileInfo = current($this->_uploadHandler->getFileInfo());
            } else {
                return array('error' => true, 'result' => $this->_uploadHandler->getMessages());
            }

            $animatedGif = false;
            if($fileInfo['type'] === 'image/gif'){
                $animatedGif = Tools_Image_Tools::isAnimatedGif($fileInfo['tmp_name'], $fileInfo['type']);
            }

            if ($resize) {
                $status = Tools_Image_Tools::batchResize($fileInfo['tmp_name'], $savePath);
            } else {
                $status = true;
            }
            if (isset($this->_helper->session->imageQualityPreview) && !$animatedGif) {
                unset($this->_helper->session->imageQualityPreview);
                Tools_Image_Tools::optimizeImage($fileInfo['tmp_name'], self::PREVIEW_IMAGE_OPTIMIZE);
            }
            if (isset($this->_helper->session->imageQuality) && !$animatedGif) {
                Tools_Image_Tools::optimizeOriginalImage($fileInfo['tmp_name'], $savePath, $this->_helper->session->imageQuality);
            }

            return array('error' => ($status !== true), 'result' => $status);
        }

        return array('error' => true, 'result' => $this->_uploadHandler->getMessages());
    }

    /**
     * Handler for files uploader
     * @return array
     */
    private function _uploadFiles($savePath = null)
    {
        $this->_uploadHandler->clearValidators();
        $this->_uploadHandler->clearFilters();

        if (!$savePath) {
            $savePath = $this->_getSavePath();
        }

        $fileInfo = $this->_uploadHandler->getFileInfo();
        $file = reset($fileInfo);
        preg_match('~[^\x00-\x1F"<>\|:\*\?/]+\.[\w\d]{2,8}$~iU', $file['name'], $match);
        if (!$match) {
            return array('result' => 'Corrupted filename', 'error' => true);
        }

        $this->_uploadHandler->addFilter('Rename', array(
            'target' => $savePath . DIRECTORY_SEPARATOR . $file['name'],
            'overwrite' => true
        ));

        //Adding file extension validation
        $this->_uploadHandler->addValidator('Extension', false, 'xml,csv,doc,zip,jpg,png,bmp,gif,xls,pdf,docx,txt,xlsx,mp3,avi,mpeg,mp4,webm');
        //Adding mime types validation
        $this->_uploadHandler->addValidator('MimeType', true, array('application/pdf','application/xml', 'application/zip', 'text/csv', 'text/plain', 'image/png','image/jpeg',
                'image/gif', 'image/bmp', 'application/msword', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','audio/mpeg3','audio/mpeg','video/avi','video/x-msvideo','video/mp4','video/mpeg', 'video/mp4'));

        if ($this->_uploadHandler->isUploaded() && $this->_uploadHandler->isValid()) {
            try {
                $this->_uploadHandler->receive();
            } catch (Exceptions_SeotoasterException $e) {
                $response = array('result' => $e->getMessage(), 'error' => true);
            }
        }

        $response = array('result' => $this->_uploadHandler->getMessages(), 'error' => !$this->_uploadHandler->isReceived());

        return $response;
    }

    /**
     * Handler for "upload media" section
     */
    private function _uploadMedia()
    {
        $this->_uploadHandler->clearValidators();
        $this->_uploadHandler->clearFilters();
        $miscConfig = Zend_Registry::get('misc');

        $imageQuality = $this->getRequest()->getParam('quality');
        if (isset($imageQuality)) {
            $this->_helper->session->imageQuality = $imageQuality;
        }

        $savePath = $this->_getSavePath();

        switch ($this->_getMimeType()) {
            case 'image/png':
            case 'image/jpg':
            case 'image/jpeg':
            case 'image/gif':
                $result = $this->_uploadImages($savePath);
                break;
            default:
                $result = $this->_uploadFiles($savePath);
                break;
        }
        if (isset($this->_helper->session->imageQuality)) {
            unset($this->_helper->session->imageQuality);
        }
        return $result;
    }

    /**
     * Method get a 'folder' name from request array and checks if this folder exists.
     * If not it creates this folder
     * @return string directory path or false if error
     */
    private function _getSavePath()
    {
        $folder = trim(preg_replace('~[^\w]+|[\s\-]+~ui', '-', filter_var($this->getRequest()->getParam('folder'), FILTER_SANITIZE_STRING)), '-');
        if (!$folder || empty($folder)) {
            return array('error' => true, 'result' => 'No files uploaded. Please select folder.');
        }
        $folder = trim($folder, ' \/');
        $folderValidator = new Zend_Validate_Regex('~^[^\x00-\x1F"<>\|:\*\?/]+$~');
        if (!$folderValidator->isValid($folder)) {
            return array('error' => true, 'result' => 'Bad folder name');
        }
        $savePath = $this->_websiteConfig['path'] . $this->_websiteConfig['media'] . $folder . DIRECTORY_SEPARATOR;
        if (!is_dir($savePath)) {
            try {
                Tools_Filesystem_Tools::mkDir($savePath);
            } catch (Exceptions_SeotoasterException $e) {
                error_log($e->getMessage());
                return false;
            }
        }
        return realpath($savePath);
    }

    private function _uploadTemplatepreview()
    {
        $miscConfig = Zend_Registry::get('misc');

        $currentTheme = $this->_helper->config->getConfig('currentTheme');

        $savePath = $this->_websiteConfig['path'] . $this->_themeConfig['path'] . $currentTheme . DIRECTORY_SEPARATOR . $this->_themeConfig['templatePreview'];

        $name = trim($this->getRequest()->getParam('templateName'));

        $fileMime = $this->_getMimeType();

        switch ($fileMime) {
            case 'image/png':
                $newName = $name . '.png';
                break;
            case 'image/jpg':
            case 'image/jpeg':
                $newName = $name . '.jpg';
                break;
            case 'image/gif':
                $newName = $name . '.gif';
                break;
            default:
                return false;
                break;
        }

        if (!$name || empty ($name)) {
            return false;
        }
        $newImageFile = $savePath . $newName;

        //checking for existing images with same name ...
        if (!is_dir($savePath)) {
            if (!Tools_Filesystem_Tools::mkDir($savePath)) {
                return false;
            }
        }
        $existingImages = glob($savePath . $name . '.{png,jpeg,jpg,gif}', GLOB_BRACE);
        // ...and removing them
        foreach ($existingImages as $img) {
            Tools_Filesystem_Tools::deleteFile($img);
        }

        $this->_uploadHandler->addFilter('Rename',
            array('target' => $newImageFile,
                'overwrite' => true));
        $result = $this->_uploadImages($savePath, false);

        if ($result['error'] == false) {
            Tools_Image_Tools::resize($newImageFile, $miscConfig['templatePreviewWidth'], true);
            $result['thumb'] = 'data:' . $fileMime . ';base64,' . base64_encode(Tools_Filesystem_Tools::getFile($newImageFile));
        }

        return $result;
    }

    private function _uploadPagepreview()
    {
        $miscConfig = Zend_Registry::get('misc');
        $configTeaserSize = $this->_helper->config->getConfig('teaserSize');

        $savePath = $this->_websiteConfig['path'] . $this->_websiteConfig['tmp'];

        $fileMime = $this->_getMimeType();
        switch ($fileMime) {
            case 'image/png':
                $newName = '.png';
                break;
            case 'image/jpg':
            case 'image/jpeg':
                $newName = '.jpg';
                $this->_helper->session->imageQualityPreview = self::PREVIEW_IMAGE_OPTIMIZE;
                break;
            case 'image/gif':
                $newName = '.gif';
                break;
            default:
                return false;
                break;
        }

        $newName = md5(microtime(1)) . $newName;
        $newImageFile = $savePath . $newName;

        $this->_uploadHandler->addFilter('Rename',
            array('target' => $newImageFile,
                'overwrite' => true));
        $result = $this->_uploadImages($savePath, false);

        if ($result['error'] == false) {
            if (!Tools_Image_Tools::isAnimatedGif($newImageFile, $fileMime)) {
                Tools_Image_Tools::resize($newImageFile, (($configTeaserSize) ? $configTeaserSize : $miscConfig['pageTeaserSize']), true);
            }
            $result['src'] = $this->_helper->website->getUrl() . $this->_websiteConfig['tmp'] . $newName;
        }

        return $result;
    }


    private function _uploadPlugin()
    {
        $this->_uploadHandler->addValidator('Extension', false, 'zip');
        if ($this->_checkMime) {
            $this->_uploadHandler->addValidator(new Validators_MimeType(array('application/zip')), false);
        }
        $pluginArchive = $this->_uploadHandler->getFileInfo();

        if (!$this->_uploadHandler->isValid()) {
            return array('error' => true);
        }

        $destination = $this->_uploadHandler->getDestination();

        $zip = new ZipArchive();
        $zip->open($pluginArchive['file']['tmp_name']);

        $unzipped = $zip->extractTo($destination);

        if ($unzipped !== true) {
            return array(
                'name' => $pluginArchive['file']['name'],
                'error' => 'Can\'t extract zip file to tmp directory'
            );
        }

        $pluginName = str_replace('.zip', '', $pluginArchive['file']['name']);

        $validateMessage = $this->_validatePlugin($pluginName);
        $miscConfig = Zend_Registry::get('misc');
        if ($validateMessage === true) {
            $destinationDir = $this->_websiteConfig['path'] . $miscConfig['pluginsPath'];
            if (is_dir($destinationDir . $pluginName)) {
                Tools_Filesystem_Tools::deleteDir($destinationDir . $pluginName);
            }
            $res = $zip->extractTo($destinationDir);
            $zip->close();
            Tools_Filesystem_Tools::deleteDir($destination . '/' . $pluginName);
        } else {
            $zip->close();
            return array(
                'name' => $pluginArchive['file']['name'],
                'error' => $validateMessage
            );
        }
        return array(
            'error' => false,
            'name' => $pluginArchive['file']['name'],
            'type' => $pluginArchive['file']['type'],
            'size' => $pluginArchive['file']['size'],
            'pluginname' => $pluginName
        );
    }

    private function _validatePlugin($pluginName)
    {
        $pluginFolder = realpath($this->_uploadHandler->getDestination() . '/' . $pluginName);
        if ($pluginFolder === false) {
            return 'Plugin directory don\'t match the archive name.';
        }
        if (!is_dir($pluginFolder)) {
            return 'Can not create folder for unpack zip file. 0peration not permitted.';
        }


        $listFiles = Tools_Filesystem_Tools::scanDirectory($pluginFolder);
        if (empty($listFiles)) {
            return 'Your plugin directory is empty.';
        }

        if (!preg_match("/^[a-zA-Z-0-9]{1,255}$/", $pluginName)) {
            return 'Theme name is invalid. Only letters, digits and dashes allowed.';
        }

        if (!in_array(ucfirst($pluginName) . '.php', $listFiles)) {
            return 'Plugin main file doesn\'t exist or has a wrong name';
        }

        if (!in_array('readme.txt', $listFiles)) {
            return 'File "readme.txt" doesn\'t exist.';
        }

        return true;

    }

    protected function _getMimeType()
    {
        if (extension_loaded('fileinfo')) {
            return $this->_uploadHandler->getMimeType();
        }
        $files = $this->_uploadHandler->getFileInfo();
        if (empty($files)) {
            return false;
        }
        $file = reset($files);
        unset($files);
        if (function_exists('getimagesize')) {
            $info = getimagesize($file['tmp_name']);
            return $info !== false ? $info['mime'] : false;
        }

        return false;
    }
}
