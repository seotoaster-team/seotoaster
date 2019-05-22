<?php
/**
 * MediaController
 * Used for manipulation of image/files
 *
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Backend_MediaController extends Zend_Controller_Action
{
    private $_translator = null;
    private $_websiteConfig = null;

    private $_mimetypesFormats = array(
        '*.doc',
        '*.csv',
        '*.txt',
        '*.docx',
        '*.xls',
        '*.xlsx',
        '*.pdf',
        '*.xml',
        '*.zip',
        '*.jpg',
        '*.png',
        '*.bmp',
        '*.gif',
        '*.webm',
        '*.ogg',
        '*.ogv',
        '*.dwg',
        '*.vcf',
        '*.mp3',
        '*.avi',
        '*.mpeg',
        '*.mp4'
    );

    public function  init()
    {
        parent::init();
        if (!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_MEDIA)) {
            $this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
        }
        $this->view->websiteUrl = $this->_helper->website->getUrl();

        $this->_websiteConfig = Zend_Registry::get('website');

        $this->_translator = Zend_Registry::get('Zend_Translate');

        $this->_helper->AjaxContext()->addActionContexts(
            array(
                'getdirectorycontent' => 'json',
                'removefile'          => 'json',
                'loadfolders'         => 'json'
            )
        )->initContext('json');
    }

    /**
     * Renders "Upload things" screen
     */
    public function uploadthingsAction()
    {
        //creating list of folder in 'images' directory
        $this->view->listFolders = array('select folder') +  $this->_getFoldersList();
        // if folder selected from somewhere else (using this feature when click upload things on editor screen)
        if (($folder = $this->getRequest()->getParam('folder')) != '') {
            $this->view->currFolder = $folder;
            $picturesPath = $this->_websiteConfig['media'] . $folder . '/small/';
            if(is_dir($this->_websiteConfig['path'] . $picturesPath)){
                $listPictures = Tools_Filesystem_Tools::scanDirectory($this->_websiteConfig['path'] . $picturesPath);

                if(!empty($listPictures)){
                    $this->view->listPictures = $listPictures;
                    $this->view->picturesPath = $picturesPath;
                }
            }

            $folderPath = realpath($this->_websiteConfig['path'] . $this->_websiteConfig['media'] . $folder);
            if(!empty($folderPath)){
                $this->view->filesList = array();
                $listFiles = Tools_Filesystem_Tools::scanDirectory($folderPath, false, false);
                foreach ($listFiles as $item) {
                    if (!is_dir($folderPath . DIRECTORY_SEPARATOR . $item)) {
                        array_push($this->view->filesList, array('name' => $item));
                    }
                }
            }
        }

        $secureToken = Tools_System_Tools::initSecureToken(Tools_System_Tools::ACTION_PREFIX_REMOVETHINGS);
        $this->view->secureToken = $secureToken;
        $this->view->mimeTypes = $this->_mimetypesFormats;
        $uploadMaxSize = ini_get('upload_max_filesize');
        $this->view->uploadMaxSize = !empty($uploadMaxSize) ? $uploadMaxSize : '';

        $this->view->helpSection = 'uploadthings';
    }

    /**
     * Renders "Remove things" screen
     */
    public function removethingsAction()
    {
        $listFolders = Tools_Filesystem_Tools::scanDirectoryForDirs(
            $this->_websiteConfig['path'] . $this->_websiteConfig['media']
        );
        if (!empty ($listFolders)) {
            $listFolders = array_combine($listFolders, $listFolders);
        }
        $secureToken = Tools_System_Tools::initSecureToken(Tools_System_Tools::ACTION_PREFIX_REMOVETHINGS);
        $this->view->secureToken = $secureToken;
        $this->view->helpSection = 'removethings';
        $this->view->listFolders = array(
                    'selectFolder' => $this->_translator->translate(
                                'select folder'
                            )
                ) + $listFolders;
    }

    /**
     * Method for loading directory content via AJAX call
     * @return JSON
     */
    public function getdirectorycontentAction()
    {
        if ($this->getRequest()->isPost()) {
            $folderName = filter_var($this->getRequest()->getParam('folder'), FILTER_SANITIZE_STRING);
            $folderPath = realpath($this->_websiteConfig['path'] . $this->_websiteConfig['media'] . $folderName);
            //retrieve content for given folder
            if (!$folderName || $folderPath === false || (strpos($folderName, '..' . DIRECTORY_SEPARATOR) !== false)) {
                $this->view->error = 'Wrong folder specified';
                return false;
            }
            $this->view->imageList = array();
            if (is_dir($folderPath . DIRECTORY_SEPARATOR . 'original')) {
                $listImages = Tools_Filesystem_Tools::scanDirectory(
                    $folderPath . DIRECTORY_SEPARATOR . 'original',
                    false,
                    false
                );
                $configHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
                $bisabledRenamedImagePrefixes = $configHelper->getConfig('bisabledRenamedImagePrefixes');

                if(isset($bisabledRenamedImagePrefixes)) {
                    $bisabledRenamedImagePrefixes = json_decode($bisabledRenamedImagePrefixes, false);
                }

                foreach ($listImages as $image) {
                    $imgInfo   = getimagesize($this->_helper->website->getUrl() . $this->_websiteConfig['media'] . $folderName . '/original/' . $image);
                    $imgMimeType   = $imgInfo['mime'];

                    $imageExtension = '';
                    switch ($imgMimeType) {
                        case 'image/gif':
                            $imageExtension = '.gif';
                            break;
                        case 'image/jpeg':
                            $imageExtension = '.jpg';
                            break;
                        case 'image/png':
                            $imageExtension = '.png';
                            break;
                        case 'image/bmp':
                            $imageExtension = '.bmp';
                            break;
                    }

                    $clearImgName = str_replace($imageExtension, '', $image);

                    if(!empty($bisabledRenamedImagePrefixes) && is_array($bisabledRenamedImagePrefixes)) {
                        $disabledEdit = '';
                        foreach ($bisabledRenamedImagePrefixes as $prefix) {
                          $denyedPrefixExist = preg_match('/^'.$prefix.'/i', $clearImgName);
                          if($denyedPrefixExist) {
                              $disabledEdit = 'disabled';
                          }
                        }
                    }

                    array_push(
                        $this->view->imageList,
                        array(
                            'name' => $image,
                            'src'  => Tools_Content_Tools::applyMediaServers(
                                        $this->_helper->website->getUrl(
                                        ) . $this->_websiteConfig['media'] . $folderName . '/original/' . $image
                                    ),
                            'clearImgName' => $clearImgName,
                            'imgExtension' => $imageExtension,
                            'disabledEdit' => $disabledEdit
                        )
                    );
                }
            }
            $this->view->filesList = array();
            $listFiles = Tools_Filesystem_Tools::scanDirectory($folderPath, false, false);
            foreach ($listFiles as $item) {
                if (!is_dir($folderPath . DIRECTORY_SEPARATOR . $item)) {
                    array_push($this->view->filesList, array('name' => $item));
                }
            }
        } else {
            $this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
        }
    }

    /**
     * @throws Exceptions_SeotoasterException
     */
    public function renamefileAction() {
        $responseHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('response');
        if ($this->getRequest()->isPost()) {
            $fileNewName = $this->getRequest()->getParam('fileNewName');
            $fileOldName = $this->getRequest()->getParam('fileOldName');
            $folderName = $this->getRequest()->getParam('folderName');

            $filterChain = new Zend_Filter();
            $filterChain->addFilter(new Zend_Filter_StringTrim())
                ->addFilter(new Zend_Filter_StringToLower('UTF-8'))
                ->addFilter(new Zend_Filter_PregReplace(array('match' => '/[^\w\d_]+/u', 'replace' => '-')));

            $fileNewName = $filterChain->filter($fileNewName);

            if(empty($fileNewName)) {
                $responseHelper->fail($this->_translator->translate('File name can\'t be empty!'));
            }

            if (empty ($folderName)) {
                $responseHelper->fail($this->_translator->translate('No folder specified'));
            }

            $tokenToValidate = $this->getRequest()->getParam(Tools_System_Tools::CSRF_SECURE_TOKEN, false);
            $valid = Tools_System_Tools::validateToken($tokenToValidate, Tools_System_Tools::ACTION_PREFIX_REMOVETHINGS);
            if (!$valid) {
                $responseHelper->fail($this->_translator->translate('Token not valid'));
            }
            $fileExtension = $this->getRequest()->getParam('fileExtension');


            $listFolders = Tools_Filesystem_Tools::scanDirectoryForDirs(
                $this->_websiteConfig['path'] . $this->_websiteConfig['media'] . $folderName
            );
            if (!empty ($listFolders)) {
                foreach ($listFolders as $key => $folder) {
                    $fileOldPath   = $this->_websiteConfig['path'] . $this->_websiteConfig['media'] . $folderName . '/'. $folder .'/' . $fileOldName . $fileExtension;
                    if(!file_exists($fileOldPath) || $folder == 'product') {
                        continue;
                    }

                    $fileNewPath   = $this->_websiteConfig['path'] . $this->_websiteConfig['media'] . $folderName . '/'. $folder .'/' . $fileNewName . $fileExtension;

                    if(file_exists($fileNewPath)) {
                        $responseHelper->fail('');
                    }

                    rename($fileOldPath, $fileNewPath);
                }

                $oldFileName = $fileOldName . $fileExtension;
                $newFileName = $fileNewName . $fileExtension;

                Application_Model_Mappers_ContainerMapper::getInstance()->replaceSearchedValue($oldFileName, $newFileName, $fileOldName, $fileNewName);
                Application_Model_Mappers_TemplateMapper::getInstance()->replaceSearchedValue($oldFileName, $newFileName, $fileOldName, $fileNewName);
                Application_Model_Mappers_LinkContainerMapper::getInstance()->replaceSearchedValue($oldFileName, $newFileName);

                $responseHelper->success(array('fileNewName' => $fileNewName));
            }
        } else {
            $responseHelper->fail('');
        }
    }

    /**
     * Action used for removing images/files from media catalog
     * for AJAX request
     * @return JSON
     */
    public function removefileAction()
    {
        if ($this->getRequest()->isPost()) {
            $folderName = $this->getRequest()->getParam('folder');
            if (empty ($folderName)) {
                $this->view->errorMsg = $this->_translator->translate('No folder specified');
                return false;
            }
            $tokenToValidate = $this->getRequest()->getParam(Tools_System_Tools::CSRF_SECURE_TOKEN, false);
            $valid = Tools_System_Tools::validateToken($tokenToValidate, Tools_System_Tools::ACTION_PREFIX_REMOVETHINGS);
            if (!$valid) {
                $this->view->errorMsg = $this->_translator->translate('Token not valid');
                return false;
            }

            $removeImages = $this->getRequest()->getParam('removeImages');
            $removeFiles = $this->getRequest()->getParam('removeFiles');
            $errorList = array();
            $folderPath = realpath($this->_websiteConfig['path'] . $this->_websiteConfig['media'] . $folderName);

            if (!isset ($removeFiles) && !isset ($removeImages)) {
                $this->view->errorMsg = $this->_translator->translate('Nothing to remove');
            }

            if (!$folderPath || !is_dir($folderPath)) {
                $this->view->errorMsg = $this->_translator->translate('No such folder');
                return false;
            }

            //list of removed files
            $deleted = array();
            //processing images
            if (isset($removeImages) && is_array($removeImages)) {
                foreach ($removeImages as $imageName) {
                    //checking if this image in any container
                    $pages = $this->_checkFileInContent($folderName . '%' . $imageName);
                    if (!empty ($pages)) {
                        array_push($errorList, array('name' => $imageName, 'errors' => $pages));
                    } else {
                        // going to remove image
                        try {
                            $result = Tools_Image_Tools::removeImageFromFilesystem($imageName, $folderName);
                            if ($result !== true) {
                                array_push($errorList, array('name' => $imageName, 'errors' => $result));
                            } else {

                                //cleaning out the images related widgets
                                $this->_helper->cache->clean(
                                    null,
                                    null,
                                    array(
                                        'Widgets_Gal_Gal'
                                    )
                                );

                                array_push($deleted, $imageName);
                            }
                        } catch (Exceptions_SeotoasterException $e) {
                            error_log($e->getMessage() . PHP_EOL . $e->getTraceAsString());
                        }
                    }
                }
            }

            //processing files
            if (isset($removeFiles) && is_array($removeFiles)) {
                foreach ($removeFiles as $fileName) {
                    if (!is_file($folderPath . DIRECTORY_SEPARATOR . $fileName)) {
                        $errorList[$fileName] = $this->_translator->translate(
                            $folderPath . DIRECTORY_SEPARATOR . $fileName . ': file not found'
                        );
                    }
                    //checking if this image in any container
                    $pages = $this->_checkFileInContent($fileName);
                    if (!empty ($pages)) {
                        array_push($errorList, array('name' => $fileName, 'errors' => $pages));
                    }
                    try {
                        $result = Tools_Filesystem_Tools::deleteFile($folderPath . DIRECTORY_SEPARATOR . $fileName);
                        if ($result !== true) {
                            array_push(
                                $errorList,
                                array(
                                    'name'   => $fileName,
                                    'errors' => $this->_translator->translate("Can't remove file. Permission denied")
                                )
                            );
                        } else {
                            array_push($deleted, $fileName);
                        }
                    } catch (Exception $e) {
                        error_log($e->getMessage() . PHP_EOL . $e->getTraceAsString());
                    }

                }
            }
            if (!empty($deleted)) {
                $folderContent = Tools_Filesystem_Tools::scanDirectory($folderPath, false, true);
                if (empty($folderContent)) {
                    try {
                        $this->view->folderRemoved = Tools_Filesystem_Tools::deleteDir($folderPath);
                    } catch (Exception $e) {
                        error_log($e->getMessage());
                    }
                }
            }
            $this->view->errors = empty ($errorList) ? false : $errorList;
            $this->view->deleted = $deleted;
        } else {
            $this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
        }
    }

    public function loadfoldersAction()
    {
        $this->view->responseText = $this->_getFoldersList($this->getRequest()->getParam('img', false));
    }


    /**
     * Checks if file/image is linked in any content and return list of pages where it used
     * @param string $filename Name of file
     * @return array List of pages where file linked
     */
    private function _checkFileInContent($filename)
    {
        $containers = Application_Model_Mappers_ContainerMapper::getInstance()->findByContent($filename);

        // formatting list of pages where image used in
        $usedOnPages = array();

        if (!empty ($containers)) {
            foreach ($containers as $container) {
                $page = Application_Model_Mappers_PageMapper::getInstance()->find($container->getPageId());
                if ($page !== null && !in_array($page->getUrl(), $usedOnPages)) {
                    array_push($usedOnPages, $page->getUrl());
                }
            }
        }

        return $usedOnPages;
    }

    private function _getFoldersList($imagesOnly = false)
    {
        $listFolders = Tools_Filesystem_Tools::scanDirectoryForDirs(
            $this->_websiteConfig['path'] . $this->_websiteConfig['media']
        );
        if (!empty ($listFolders)) {
            if ($imagesOnly) {
                foreach ($listFolders as $key => $folder) {
                    if (!is_dir($this->_websiteConfig['path'] . $this->_websiteConfig['media'] . $folder . '/small')) {
                        unset($listFolders[$key]);
                    }
                }
            }
            $listFolders = array_combine($listFolders, $listFolders);
        }
        return $listFolders;
    }
}
