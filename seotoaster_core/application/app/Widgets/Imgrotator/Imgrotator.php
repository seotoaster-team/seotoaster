<?php

/**
 * Imgrotator {$imgrotator:folder:slideshow/notslideshow:time(if slideshow):maxwidth:maxheight:effect:pager:prevnext}
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_Imgrotator_Imgrotator extends Widgets_Abstract
{
    /**
     * Default swap time in seconds
     */
    const DEFAULT_SWAP_TIME        = '2';

    const DEFAULT_SLIDER_WIDTH     = '250';

    const DEFAULT_SLIDER_HEIGHT    = '250';

    const DEFAULT_SWAP_EFFECT      = 'fade';

    const DEFAULT_PICS_FOLDER      = 'original';

    const PREFIX                   = 'ir_';

    protected $_cacheable          = false;

    private $_websiteHelper        = null;

    public static $_defaultEffects = array(
        'none'       => 'none',
        'fade'       => 'fade',
        'fadeOut'    => 'fadeOut',
        'tileSlide'  => 'tileSlide',
        'tileBlind'  => 'tileBlind',
        'scrollHorz' => 'scrollHorz',
//        'shuffle'    => 'shuffle',
        'scrollVert' => 'scrollVert',
    );

    protected function  _init()
    {
        parent::_init();
        $this->_view = new Zend_View(array('scriptPath' => dirname(__FILE__) . '/views'));
        $this->_websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $this->_view->addHelperPath('ZendX/JQuery/View/Helper/', 'ZendX_JQuery_View_Helper');
        $this->_view->websiteUrl = $this->_websiteHelper->getUrl();
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

        $configHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
        $imageFolder = self::DEFAULT_PICS_FOLDER;
        $sliderWidth = (isset($this->_options[3]) && $this->_options[3]) ? $this->_options[3]
            : self::DEFAULT_SLIDER_WIDTH;

        if ($sliderWidth <= $configHelper->getConfig('imgSmall')) {
            $imageFolder = 'small';
        }
        elseif ($sliderWidth <= $configHelper->getConfig('imgMedium')) {
            $imageFolder = 'medium';
        }
        elseif ($sliderWidth <= $configHelper->getConfig('imgLarge')) {
            $imageFolder = 'large';
        }
        $fullPathToPics = $this->_websiteHelper->getPath().$this->_websiteHelper->getMedia().$this->_options[0]
            .DIRECTORY_SEPARATOR.$imageFolder.DIRECTORY_SEPARATOR;

        $this->_view->mediaServersAllowed = $configHelper->getConfig('mediaServers');
        $this->_view->uniq         = uniqid('rotator-');
        $this->_view->sliderWidth  = (isset($this->_options[3]) && $this->_options[3]) ? $this->_options[3]
            : self::DEFAULT_SLIDER_WIDTH;
        $this->_view->sliderHeight = (isset($this->_options[4]) && $this->_options[4]) ? $this->_options[4]
            : self::DEFAULT_SLIDER_HEIGHT;
        $this->_view->swapTime     = (isset($this->_options[2]) && $this->_options[2]) ? $this->_options[2]
            : self::DEFAULT_SWAP_TIME;
        $this->_view->slideShow    = (isset($this->_options[1]) && $this->_options[1]) ? true : false;
        $files                     = Tools_Filesystem_Tools::scanDirectory($fullPathToPics, false, false);
        if ($this->_view->slideShow) {
            $this->_view->files    = $files;
        }
        else {
            $this->_view->files    = (array)$files[array_rand($files)];
        }
        $this->_view->rotatorId    = self::PREFIX . substr(md5($this->_options[0]), 0, 10);
        $this->_view->folder       = $this->_options[0] . DIRECTORY_SEPARATOR . $imageFolder . DIRECTORY_SEPARATOR;
        $this->_view->effect       = (isset($this->_options[5]) && $this->_options[5]) ? $this->_options[5]
            : self::DEFAULT_SWAP_EFFECT;
        $this->_view->pager        = (isset($this->_options[6]) && (int)$this->_options[6] === 1) ? true : false;
        $this->_view->prevnext     = (isset($this->_options[7]) && (int)$this->_options[7] === 1) ? true : false;
        $this->_view->content      = (isset($this->_options[8]) && (int)$this->_options[8] === 1) ? true : false;

        if (Zend_Registry::isRegistered('RoatorStyles')) {
            $stylesOn = false;
        } else {
            Zend_Registry::set('RoatorStyles', true);
            $stylesOn = true;
        }
        $this->_view->stylesOn = $stylesOn;
        return $this->_view->render('rotator.phtml');
    }


    public static function getWidgetMakerContent()
    {
        $translator = Zend_Registry::get('Zend_Translate');
        $view = new Zend_View(array('scriptPath' => dirname(__FILE__) . '/views'));
        $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $data = array(
            'title'   => $translator->translate('Image rotator'),
            'content' => $view->render('wmcontent.phtml'),
            'icons'   => array($websiteHelper->getUrl().'system/images/widgets/imageRotator.png')
        );
        unset($view, $translator);

        return $data;
    }
}
