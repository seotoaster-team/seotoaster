<?php

/**
 * Page garbage collector
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Page_GarbageCollector extends Tools_System_GarbageCollector
{
    protected function _runOnDefault()
    {

    }

    protected function _runOnCreate()
    {
        $this->_cleanCachedPageData();
    }

    protected function _runOnUpdate()
    {
        $this->_cleanDraftCache();
        $this->_cleanOptimized();
        $this->_cleanCachedPageData();
        $this->_resetSearchIndexRenewFlag();
        $this->_deletePreviewSubfolderCrop();
    }

    protected function _runOnDelete()
    {
        $this->_cleanDraftCache();
        $this->_removePageUrlFromContent();
        //Tools_Filesystem_Tools::saveFile('sitemap.xml', Tools_Content_Feed::generateSitemapFeed());
        Tools_Search_Tools::removeFromIndex($this->_object->getId());
        $this->_cleanCachedPageData();
        $this->_resetSearchIndexRenewFlag();
        $this->_deletePreview();
        $this->_deletePreviewSubfolderCrop();
    }

    /**
     * @todo improve/ optimize?
     */
    private function _removePageUrlFromContent()
    {
        $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Website');
        $websiteUrl    = $websiteHelper->getUrl();
        unset ($websiteHelper);
        $data = Application_Model_Mappers_LinkContainerMapper::getInstance()->findByLink(
            $websiteUrl.$this->_object->getUrl()
        );
        if (is_array($data) && !empty ($data)) {
            $containerMapper = Application_Model_Mappers_ContainerMapper::getInstance();
            foreach ($data as $containerData) {
                $container = $containerMapper->find($containerData['id_container']);

                $container->registerObserver(new Tools_Content_GarbageCollector(
                    array('action' => Tools_System_GarbageCollector::CLEAN_ONUPDATE)
                ));

                if (!$container instanceof Application_Model_Models_Container) {
                    continue;
                }
                $urlPattern = '~<a\s+.*\s*href="' . $containerData['link'] . '"\s*.*\s*>.*</a>~uUs';

                $content = preg_replace($urlPattern, '', $container->getContent());
                $container->setContent($content);
                $containerMapper->save($container);
                $container->notifyObservers();
            }
        }
    }

    private function _removeRelatedContainers()
    {
        $containerMapper = Application_Model_Mappers_ContainerMapper::getInstance();
        $containers = $containerMapper->findByPageId($this->_object->getId());
        if (!empty ($containers)) {
            foreach ($containers as $container) {
                $containerMapper->delete($container);
            }
        }
    }

    private function _cleanDraftCache()
    {
        // Cleaning draft cache if draft state of the page was changed
        $cacheHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('cache');
        $sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
        if ($this->_object->getDraft() != $sessionHelper->oldPageDraft) {
            $cacheHelper->clean(false, false, Helpers_Action_Cache::TAG_DRAFT);
        }
        unset($cacheHelper, $sessionHelper);
    }

    private function _cleanOptimized()
    {
        $optimizedDbTable = new Application_Model_DbTable_Optimized();
        $optimizedExists = $optimizedDbTable->find($this->_object->getId())->current();
        if ($optimizedExists && !$this->_object->getOptimized()) {
            $optimizedDbTable->delete(array('page_id = ?' => $this->_object->getId()));
        }
    }

    private function _resetSearchIndexRenewFlag()
    {
        $cacheHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('cache');
        $cacheHelper->clean(null, null, array('search_index_renew'));
    }

    private function _cleanCachedPageData()
    {
        $cacheHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('cache');
        $cacheHelper->clean($this->_object->getUrl(), 'pagedata_');
        $tags = array(
            'pageid_'.$this->_object->getId(),
            'Widgets_Menu_Menu',
            'Widgets_Related_Related',
            'pageTags',
            'Widgets_List_List',
            'sitemaps'
        );
        $cacheHelper->clean(false, false, $tags);
    }

    /**
     * Deleted preview from folders previews and crop
     */
    private function _deletePreview()
    {
        $websiteHelper   = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $path            = $websiteHelper->getPath();
        $previewPath     = $websiteHelper->getPreview();
        $previewCropPath = $websiteHelper->getPreviewCrop();
        $imageName       = $this->_object->getPreviewImage();

        // Mark files for deletion
        $remove = array();
        if (is_file($path.$previewPath.$imageName)) {
            array_push($remove, $path.$previewPath.$imageName);
        }

        if (is_dir($path.$previewCropPath)) {
            if (is_file($path.$previewCropPath.$imageName)) {
                array_push($remove, $path.$previewCropPath.$imageName);
            }
        }
        else {
            error_log('Not a folder:'.$path.$previewCropPath);
        }

        // Remove preview
        $this->_deleteFile($remove);
    }

    /**
     * Deleted preview from subfolders crop
     */
    private function _deletePreviewSubfolderCrop()
    {
        $websiteHelper   = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $path            = $websiteHelper->getPath();
        $previewCropPath = $websiteHelper->getPreviewCrop();
        $imageName       = $this->_object->getPreviewImage();

        // Mark files for deletion
        $remove = array();
        if (is_dir($path.$previewCropPath)) {
            // Checking cropped images to remove file
            $subFolders = Tools_Filesystem_Tools::scanDirectory($path.$previewCropPath, false, false);
            foreach ($subFolders as $folder) {
                if (!is_dir($path.$previewCropPath.DIRECTORY_SEPARATOR.$folder)) {
                    continue;
                }
                $filePath = $path.$previewCropPath.$folder.DIRECTORY_SEPARATOR.$imageName;
                if (is_file($filePath)) {
                    array_push($remove, $filePath);
                }
            }
        }
        else {
            error_log('Not a folder:'.$path.$previewCropPath);
        }

        // Remove preview
        $this->_deleteFile($remove);
    }

    /**
     * @param array $files
     */
    private function _deleteFile($files = array())
    {
        foreach ($files as $file) {
            try {
                Tools_Filesystem_Tools::deleteFile($file);
            }
            catch (Exceptions_SeotoasterException $e) {
                error_log($file.': '.$e->getMessage());
            }
        }
    }
}
