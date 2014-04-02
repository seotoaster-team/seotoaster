<?php

/**
 * Related widget
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_Related_Related extends Widgets_Abstract
{
    const REL_WORD_COUNT  = '2';

    const REL_DESC_LENGTH = '250';

    const REL_MAX_REZULT  = '5';

    protected function _init()
    {
        parent::_init();
        $this->_view             = new Zend_View(array('scriptPath' => dirname(__FILE__).'/views'));
        $website                 = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $this->_view->websiteUrl = $website->getUrl();
        $this->useImage          = false;
        $this->cropParams        = array();
        $this->cropSizeSubfolder = '';
        array_push($this->_cacheTags, __CLASS__);
    }


    protected function _load()
    {
        $keywordCount = (isset($this->_options[0]) ? $this->_options[0] : self::REL_WORD_COUNT);
        $keywords     = $this->_prepareKeywords($this->_toasterOptions['metaKeywords']);
        $currPageId   = $this->_toasterOptions['id'];
        $related      = array();
        if (sizeof($keywords) >= sizeof($keywordCount)) {
            $pages = Application_Model_Mappers_PageMapper::getInstance()->fetchAll('id != ' . $currPageId);
            foreach ($pages as $page) {
                $pageKeywords = $this->_prepareKeywords($page->getMetaKeywords());
                if (sizeof(array_intersect($keywords, $pageKeywords)) >= $keywordCount) {
                    $related[] = $page;
                }
            }
        }

        // Image output options
        if (isset($this->_options[2]) && ($this->_options[2] == 'img' || $this->_options[2] == 'imgc')) {
            $this->useImage = $this->_options[2];
        }
        elseif (isset($this->_options[2]) && strpos($this->_options[2], 'imgc-') !== false) {
            preg_match('/^imgc-([0-9]+)x?([0-9]*)/i', $this->_options[2], $this->cropParams);
            if (isset($this->cropParams[1], $this->cropParams[2])
                && is_numeric($this->cropParams[1])
                && $this->cropParams[2] == ''
            ) {
                $this->cropParams[2] = $this->cropParams[1];
            }
            unset($this->cropParams[0]);
            $this->useImage = 'imgc';
        }

        if (!empty($this->cropParams)) {
            $this->cropSizeSubfolder = implode($this->cropParams, '-').DIRECTORY_SEPARATOR;
        }

        // Create a folder crop-size subfolder
        if ($this->useImage == 'imgc' && $this->cropSizeSubfolder != '') {
            $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
            $pathPreview   = $websiteHelper->getPath().$websiteHelper->getPreviewCrop().$this->cropSizeSubfolder;
            if (!is_dir($pathPreview)) {
                Tools_Filesystem_Tools::mkDir($pathPreview);
            }
        }

        $this->_view->useImage          = $this->useImage;
        $this->_view->cropParams        = $this->cropParams;
        $this->_view->cropSizeSubfolder = $this->cropSizeSubfolder;
        $this->_view->descLength        = self::REL_DESC_LENGTH;
        $this->_view->related = ($this->_options[1] >= sizeof($related)) ? $related
            : array_slice($related, 0, $this->_options[1]);

        return $this->_view->render('related.phtml');
    }

    private function _prepareKeywords($keywords)
    {
        return array_map(
            function ($value) {
                return trim($value);
            },
            explode(',', $keywords)
        );
    }

    public static function getWidgetMakerContent()
    {
        $translator    = Zend_Registry::get('Zend_Translate');
        $view          = new Zend_View(array('scriptPath' => dirname(__FILE__).'/views'));
        $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $data          = array(
            'title'   => $translator->translate('Related pages'),
            'content' => $view->render('wmcontent.phtml'),
            'icons'   => array($websiteHelper->getUrl().'system/images/widgets/relatedPages.png')
        );
        unset($view, $translator);

        return $data;
    }
}

