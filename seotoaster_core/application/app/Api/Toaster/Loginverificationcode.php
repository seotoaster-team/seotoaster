<?php
/**
 * Seotoaster login verification API
 *
 */

class Api_Toaster_Loginverificationcode extends Api_Service_Abstract
{

    protected $_accessList = array(
        Tools_Security_Acl::ROLE_GUEST => array('allow' => array('post'))
    );

    public function init()
    {
        $this->_websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $this->_configHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
        $this->_sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
        $this->_translator = Zend_Registry::get('Zend_Translate');
    }

    public function getAction()
    {

    }

    public function postAction()
    {
        $secureToken = $this->_request->getParam(Tools_System_Tools::CSRF_SECURE_TOKEN, false);
        $tokenValid = Tools_System_Tools::validateToken($secureToken, Tools_System_Tools::ACTION_PREFIX_LOGIN);
        if (!$tokenValid) {
            return array('error' => 1, 'message' => $this->_translator->translate('Please re-authenticate.'));
        }

        Tools_System_MfaTools::cleanVerificationUserId();

        if (!isset($this->_sessionHelper->verificationCodeUserId)) {
            return array('error' => 1, 'message' => $this->_translator->translate('Please re-authenticate.'));
        }

        Tools_System_MfaTools::sendMfaNotification($this->_sessionHelper->verificationCodeUserId);

        return array('error' => 0, 'message' => $this->_translator->translate('Verification code has been sent'));
    }

    public function putAction()
    {
    }

    public function deleteAction()
    {
    }
}