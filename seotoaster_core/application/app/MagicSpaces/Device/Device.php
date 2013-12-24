<?php
/**
 * Device MagicSpace show/hide space content for specified device
 */
class MagicSpaces_Device_Device extends Tools_MagicSpaces_Abstract {

    protected function _run() {
        $mobileHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('mobile');
        $sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('session');

        if(!isset($this->_params[0])) {
            return $this->_spaceContent;
        }
        $devices = explode(',', preg_replace('/\s+/u', '', $this->_params[0]));
        if(empty($devices)) {
            return '';
        }

        if (isset($sessionHelper->mobileSwitch)) {
            $isMobile = $sessionHelper->mobileSwitch;
        } else {
            $isMobile = $mobileHelper->isMobile();
        }

        if ($isMobile) {
            $deviceType = $mobileHelper->isTablet() ? Widgets_Mobile_Mobile::MODE_TABLET : Widgets_Mobile_Mobile::MODE_MOBILE ;
        } else {
            $deviceType = Widgets_Mobile_Mobile::MODE_DESKTOP;
        }
        if (!in_array($deviceType, $devices)) {
            return '';
        }
    }

}