<?php
/**
 * Created by JetBrains PhpStorm.
 * User: seotoaster
 * Date: 11/25/13
 * Time: 1:12 PM
 * To change this template use File | Settings | File Templates.
 */

class MagicSpaces_Device_Device extends Tools_MagicSpaces_Abstract {

    protected function _run() {
        $mobileHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('mobile');
        if(!isset($this->_params[0])) {
            return $this->_spaceContent;
        }
        $devices = explode(',', preg_replace('/\s+/u', '', $this->_params[0]));
        if(empty($devices)) {
            return '';
        }
        if ($mobileHelper->isMobile()) {
            $deviceType = $mobileHelper->isTablet() ? Widgets_Mobile_Mobile::MODE_TABLET : Widgets_Mobile_Mobile::MODE_MOBILE ;
        } else {
            $deviceType = Widgets_Mobile_Mobile::MODE_DESKTOP;
        }
        if (!in_array($deviceType, $devices)) {
            return '';
        }
    }

}