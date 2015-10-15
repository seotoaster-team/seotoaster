<?php

class Widgets_Gal_Gal extends Widgets_Abstract
{
    const DEFAULT_THUMB_SIZE = '250';

    const WITH_CONTAINER_CONTENT = 'withContent';

    private $_websiteHelper  = null;

    protected function _init()
    {
        parent::_init();
        $this->_view             = new Zend_View(array('scriptPath' => dirname(__FILE__).'/views'));
        $this->_websiteHelper    = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $this->_view->websiteUrl = $this->_websiteHelper->getUrl();
        array_push($this->_cacheTags, __CLASS__);
    }

    protected function _load()
    {
        if (!is_array($this->_options)
            || empty($this->_options)
            || !isset($this->_options[0])
            || !$this->_options[0]
            || preg_match('~^\s*$~', $this->_options[0])
        ) {
            throw new Exceptions_SeotoasterException($this->_translator->translate('You should specify folder.'));
        }

        $path = $this->_websiteHelper->getPath().$this->_websiteHelper->getMedia().$this->_options[0]
            .DIRECTORY_SEPARATOR;
        $configHelper        = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
        $mediaServersAllowed = $configHelper->getConfig('mediaServers');
        unset($configHelper);

        if (!is_dir($path)) {
            throw new Exceptions_SeotoasterException($path . ' is not a directory.');
        }

        $pathFileOriginal = $path.Tools_Image_Tools::FOLDER_ORIGINAL.DIRECTORY_SEPARATOR;
        $sourceImages     = Tools_Filesystem_Tools::scanDirectory($pathFileOriginal);
        $useCrop          = isset($this->_options[2]) ? (boolean)$this->_options[2] : false;
        $galFolder        = $path.(($useCrop) ? Tools_Image_Tools::FOLDER_CROP : Tools_Image_Tools::FOLDER_THUMBNAILS)
            .DIRECTORY_SEPARATOR;
        if (!is_dir($galFolder)) {
            Tools_Filesystem_Tools::mkDir($galFolder);
        }
        // Changing the image to fit the size
        if ($useCrop && isset($this->_options[2]) && $this->_options[2] != '1') {
            if (strpos($this->_options[2], 'x') !== false) {
                list($width, $height) = explode('x', $this->_options[2]);
            }
            else {
                $width  = $this->_options[2];
                $height = $this->_options[2];
            }
            $galFolder .= $width.'-'.$height.DIRECTORY_SEPARATOR;
        }
        else {
            $width  = isset($this->_options[1]) ? $this->_options[1] : self::DEFAULT_THUMB_SIZE;
            $height = 'auto';
        }

        if (!is_dir($galFolder)) {
            Tools_Filesystem_Tools::mkDir($galFolder);
        }

        $websiteData = ($mediaServersAllowed) ? Zend_Registry::get('website') : null;
        $sourcePart  = str_replace($this->_websiteHelper->getPath(), $this->_websiteHelper->getUrl(), $galFolder);
        foreach ($sourceImages as $key => $image) {
            // Update image
            if (is_file($galFolder.$image)) {
                $imgInfo = getimagesize($galFolder.$image);
                if ($imgInfo[0] != $width && ($imgInfo[1] != $height || $height != 'auto')) {
                    Tools_Image_Tools::resizeByParameters(
                        $pathFileOriginal.$image,
                        $width,
                        $height,
                        !($useCrop),
                        $galFolder,
                        $useCrop
                    );
                }
            }
            // Create image
            else {
                Tools_Image_Tools::resizeByParameters(
                    $pathFileOriginal.$image,
                    $width,
                    $height,
                    !($useCrop),
                    $galFolder,
                    $useCrop
                );
            }

            if ($mediaServersAllowed) {
                $mediaServer     = Tools_Content_Tools::getMediaServer();
                $cleanWebsiteUrl = str_replace('www.', '', $websiteData['url']);
                $sourcePart      = str_replace($websiteData['url'], $mediaServer.'.'.$cleanWebsiteUrl, $sourcePart);
            }
            $sourceImages[$key] = array(
                'path' => $sourcePart.$image,
                'name' => $image
            );
        }

        $this->_view->original = str_replace($this->_websiteHelper->getPath(), $this->_websiteHelper->getUrl(), $path)
            .Tools_Image_Tools::FOLDER_ORIGINAL.DIRECTORY_SEPARATOR;
        $this->_view->folder              = $this->_options[0];
        $this->_view->images              = $sourceImages;
        $this->_view->thumbnails          = $this->_options[1];
        $this->_view->useCaption          = isset($this->_options[3]) ? (boolean)$this->_options[3] : false;
        $this->_view->galFolderPath       = $galFolder;
        $this->_view->mediaServersAllowed = $mediaServersAllowed;
        $this->_view->galFolder           = str_replace(
            $this->_websiteHelper->getPath(),
            $this->_websiteHelper->getUrl(),
            $galFolder
        );

        if (isset($this->_options[4]) && $this->_options[4]) {
            $this->_view->block = $this->_options[4];
        }

        $withContainer = array_search(self::WITH_CONTAINER_CONTENT, $this->_options);
        if ($withContainer !== false) {
            $this->_view->withContainer = true;
        }

        return $this->_view->render('gallery.phtml');
    }

    public static function getWidgetMakerContent()
    {
        $translator    = Zend_Registry::get('Zend_Translate');
        $view          = new Zend_View(array('scriptPath' => dirname(__FILE__).'/views'));
        $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $data          = array(
            'title'   => $translator->translate('Image Gallery'),
            'content' => $view->render('wmcontent.phtml'),
            'icons'   => array($websiteHelper->getUrl().'system/images/widgets/imageGallery.png')
        );
        unset($view, $translator);

        return $data;
    }
}
