<?php
class Widgets_Mobile_Mobile extends Widgets_Abstract {

    protected $_cacheable = false;

    protected function _load() {
        $content = '';

        if ('switch' == $this->_options[0]) {
            if (null !== ($mobileSwitch = Zend_Controller_Action_HelperBroker::getStaticHelper('session')->mobileSwitch)) {
                $mobileSwitch = ($mobileSwitch == 1) ?  0 : 1;
            }
            else {
                $mobileSwitch = (Zend_Controller_Action_HelperBroker::getStaticHelper('mobile')->isMobile()) ? 0 : 1;
            }

            $thisPageUrl = $this->_toasterOptions['websiteUrl'].$this->_toasterOptions['url'];
            $cssClass    = ($mobileSwitch == 1) ? ' full' : ' mobile';
            $textLink    = ($mobileSwitch == 1) ? $this->_translator->translate('Go to mobile site') : $this->_translator->translate('Go to full site');
            $content     = '<a class="widgets-mobile-switch'.$cssClass.'" href="'.$thisPageUrl.'?mobileSwitch='.$mobileSwitch.'" title="'.$textLink.'">'.$textLink.'</a>';
        }

        return $content;
    }
}