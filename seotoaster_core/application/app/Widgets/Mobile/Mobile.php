<?php
class Widgets_Mobile_Mobile extends Widgets_Abstract {

    const MODE_MOBILE = 'mobile';

    const MODE_TABLET = 'tablet';

    const MODE_DESKTOP = 'desktop';

    protected $_cacheable = false;

    /**
     * @var Helpers_Action_Session
     */
    protected $_sessionHelper;

    /**
     * @var Helpers_Action_Mobile
     */
    protected $_mobileHelper;

    protected function _init() {
        parent::_init();
        $this->_sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
        $this->_mobileHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('mobile');
    }


    protected function _load() {
        if (!empty($this->_options)) {
            $method = '_render' . ucfirst(array_shift($this->_options));
            if (method_exists($this, $method)) {
                return $this->$method();
            }
        }
    }

    /**
     * Renders link that switch between mobile and desktop templates
     * @return string Widget html
     */
    protected function _renderSwitch() {
        $mobileSwitch = $this->_sessionHelper->mobileSwitch;
        if (null !== $mobileSwitch) {
            $mobileSwitch = $mobileSwitch ? 0 : 1;
        } else {
            $mobileSwitch = $this->_mobileHelper->isMobile() ? 0 : 1;
        }

        $thisPageUrl = $this->_toasterOptions['websiteUrl'];
        if ($this->_toasterOptions['url'] !== 'index.html') {
            $thisPageUrl .= $this->_toasterOptions['url'];
        }
        $cssClass    = ($mobileSwitch == 1) ? ' full' : ' mobile';
        $textLink    = ($mobileSwitch == 1) ? $this->_translator->translate('Go to mobile site') : $this->_translator->translate('Go to full site');
        $content     = '<a class="widgets-mobile-switch'.$cssClass.'" href="'.$thisPageUrl.'?mobileSwitch='.$mobileSwitch.'" title="'.$textLink.'">'.$textLink.'</a>';

        return $content;
    }

    protected function _renderDevice() {
        // detecting on what device we are now
        if (isset($this->_sessionHelper->mobileSwitch)) {
            $isMobile = $this->_sessionHelper->mobileSwitch;
        } else {
            $isMobile = $this->_mobileHelper->isMobile();
        }

        if ($isMobile) {
            $currentDeviceType = $this->_mobileHelper->isTablet() ? self::MODE_TABLET : self::MODE_MOBILE ;
        } else {
            $currentDeviceType = self::MODE_DESKTOP;
        }

        // if nothing passed into widget options - simple return current device type
        if (empty($this->_options)) {
            return $currentDeviceType;
        }

        // parse if we got some options
        foreach($this->_options as $option) {
            list ($deviceList, $data) = explode('=', $option);
            $deviceList = explode(',', preg_replace('/\s+/u', '', $deviceList));
            if (in_array($currentDeviceType, $deviceList)){
                return $data;
            }
        }
    }

}