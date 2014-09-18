<?php

/**
 * Featured widget. Takes care about featured:area, featured:page, etc...
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_Featured_Featured extends Widgets_Abstract
{
    const AREA_DESC_LENGTH   = '250';

    const AREA_PAGES_COUNT   = '5';

    const FEATURED_TYPE_PAGE = 'page';

    const FEATURED_TYPE_AREA = 'area';

    protected function _init()
    {
        parent::_init();
        $this->_view             = new Zend_View(array('scriptPath' => dirname(__FILE__).'/views'));
        $this->_view->websiteUrl = Zend_Controller_Action_HelperBroker::getStaticHelper('website')->getUrl();
        $this->useImage          = false;
        $this->cropParams        = array();
        $this->cropSizeSubfolder = '';

        // checking if its area and random
        if (!empty($this->_options)
            && (reset($this->_options) === self::FEATURED_TYPE_AREA)
            && (1 === intval(end($this->_options)))
        ) {
            $this->_cacheable = false;
        }
    }

    protected function _load()
    {
        $featuredType = array_shift($this->_options);
        $rendererName = '_renderFeatured' . ucfirst($featuredType);

        if ($featuredType == self::FEATURED_TYPE_AREA && isset($this->_options[3])) {
            $cropOption = $this->_options[3];
        }
        elseif ($featuredType == self::FEATURED_TYPE_PAGE && isset($this->_options[2])) {
            $cropOption = $this->_options[2];
        }

        // Image output options
        if (isset($cropOption) && ($cropOption == 'img' || $cropOption == 'imgc')) {
            $this->useImage = $cropOption;
        }
        elseif (isset($cropOption) && strpos($cropOption, 'imgc-') !== false) {
            preg_match('/^imgc-([0-9]+)x?([0-9]*)/i', $cropOption, $this->cropParams);
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

        if (method_exists($this, $rendererName)) {
            return $this->$rendererName($this->_options);
        }
        throw new Exceptions_SeotoasterWidgetException($this->_translator->translate('Wrong featured type'));
    }

    private function _renderFeaturedArea($params)
    {
        if (!is_array($params)
            || empty($params)
            || !isset($params[0])
            || !$params[0]
            || preg_match('~^\s*$~', $params[0])
        ) {
            throw new Exceptions_SeotoasterWidgetException(
                $this->_translator->translate('Featured area name required.')
            );
        }

        $featuredArea = Application_Model_Mappers_FeaturedareaMapper::getInstance()->findByName($params[0]);
        if ($featuredArea === null) {
            if (!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_ADMINPANEL)) {
                return '';
            }

            return $this->_translator->translate('Featured area ').$params[0].$this->_translator->translate(
                ' does not exist'
            );
        }

        // Set limit and on/off random
        $featuredArea->setLimit((isset($params[1]) && $params[1]) ? $params[1] : self::AREA_PAGES_COUNT)
            ->setRandom((intval(end($params)) === 1) ? true : false);

        $this->_view->faPages = $featuredArea->getPages();
        $this->_view->faId    = $featuredArea->getId();
        $this->_view->faName  = $featuredArea->getName();
        $class                = current(preg_grep('/class=*/', $params));
        $this->_view->listClass = ($class !== null) ? preg_replace('/class=/', '', $class) : '';
        $this->_view->faPageDescriptionLength = (isset($params[2]) && is_numeric($params[2])) ? intval($params[2])
            : self::AREA_DESC_LENGTH;

        // Adding cache tag for this fa
        array_push($this->_cacheTags, 'fa_'.$params[0]);
        array_push($this->_cacheTags, 'pageTags');
        $areaPages = $featuredArea->getPages();
        foreach ($areaPages as $page) {
            array_push($this->_cacheTags, 'pageid_'.$page->getId());
        }

        return $this->_view->render('area.phtml');
    }

    private function _renderFeaturedPage($params)
    {
        if (!is_array($params)
            || empty($params)
            || !isset($params[0])
            || !$params[0]
            || preg_match('~^\s*$~', $params[0])
        ) {
            if (Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_CONTENT)) {
                throw new Exceptions_SeotoasterWidgetException($this->_translator->translate(
                    'Featured page id required.'
                ));
            }
            return '';
        }
        if (($page = Application_Model_Mappers_PageMapper::getInstance()->find(intval($params[0]))) === null) {
            if (Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_CONTENT)) {
                throw new Exceptions_SeotoasterWidgetException(
                    $this->_translator->translate('Page with such id is not found')
                );
            }
            return '';
        }

        $this->_view->page       = $page;
        $class                   = current(preg_grep('/class=*/', $params));
        $this->_view->listClass  = ($class !== null) ? preg_replace('/class=/', '', $class) : '';
        $this->_view->descLength = (isset($params[1]) && is_numeric($params[1])) ? intval($params[1])
            : self::AREA_DESC_LENGTH;
        array_push($this->_cacheTags, 'pageid_'.$page->getId());

        return $this->_view->render('page.phtml');
    }


    public static function getWidgetMakerContent()
    {
        $translator    = Zend_Registry::get('Zend_Translate');
        $view          = new Zend_View(array('scriptPath' => dirname(__FILE__).'/views'));
        $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $data          = array(
            'title'   => $translator->translate('List pages by tags'),
            'icons'   => array($websiteHelper->getUrl() . 'system/images/widgets/featured.png'),
            'content' => $view->render('wmcontent.phtml')
        );
        unset($view, $translator);

        return $data;
    }
}
