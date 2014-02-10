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
        $this->view->listFolders = array_merge(array('select folder'), $this->_getFoldersList());
        // if folder selected from somewhere else (using this feature when click upload things on editor screen)
        if (($folder = $this->getRequest()->getParam('folder')) != '') {
            $this->view->currFolder = $folder;
        }
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
            if (!$folderName || $folderPath === false) {
                $this->view->error = 'Wrong folder specified';
                return false;
            }
            $this->view->imageList = array();
            if (is_dir($folderPath . DIRECTORY_SEPARATOR . 'product')) {
                $listImages = Tools_Filesystem_Tools::scanDirectory(
                    $folderPath . DIRECTORY_SEPARATOR . 'product',
                    false,
                    false
                );
                foreach ($listImages as $image) {
                    array_push(
                        $this->view->imageList,
                        array(
                            'name' => $image,
                            'src'  => Tools_Content_Tools::applyMediaServers(
                                        $this->_helper->website->getUrl(
                                        ) . $this->_websiteConfig['media'] . $folderName . '/product/' . $image
                                    )
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
