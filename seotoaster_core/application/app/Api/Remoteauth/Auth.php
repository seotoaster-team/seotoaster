<?php

/**
 * Class Api_Remoteauth_Auth
 */
class Api_Remoteauth_Auth extends Api_Service_Abstract
{

    protected $_accessList = array(
        Tools_Security_Acl::ROLE_SUPERADMIN => array('allow' => array('get')),
        Tools_Security_Acl::ROLE_ADMIN => array('allow' => array('get')),
        Tools_Security_Acl::ROLE_USER => array('allow' => array('get')),
        Tools_Security_Acl::ROLE_MEMBER => array('allow' => array('get')),
        Tools_Security_Acl::ROLE_GUEST => array('allow' => array('get'))
    );

    public function getAction()
    {
        $token = filter_var($this->_request->getParam('authorizationToken'), FILTER_SANITIZE_STRING);
        if (!empty($token)) {
            $userMapper = Application_Model_Mappers_UserMapper::getInstance();
            $userModel = $userMapper->findByRemoteAuthToken($token);
            if ($userModel instanceof Application_Model_Models_User) {
                $userModel->setRemoteAuthorizationToken('');
                $additionalParams = json_decode($userModel->getRemoteAuthorizationInfo(), true);
                $userModel->setLastLogin(date(Tools_System_Tools::DATE_MYSQL));
                $userModel->setIpaddress($_SERVER['REMOTE_ADDR']);
                $sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
                $sessionHelper->setCurrentUser($userModel);
                $userMapper->save($userModel);
                $cacheHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('cache');
                $cacheHelper->clean();
                $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
                $websiteUrl = $websiteHelper->getUrl();
                $redirector = new Zend_Controller_Action_Helper_Redirector();
                if (empty($additionalParams['redirectLink'])) {
                    $redirector->gotoUrl($websiteUrl);
                }
                $redirector->gotoUrl($websiteUrl . $additionalParams['redirectLink']);

            }
        }
    }

    public function postAction()
    {
    }

    public function putAction()
    {
    }

    public function deleteAction()
    {
    }
}