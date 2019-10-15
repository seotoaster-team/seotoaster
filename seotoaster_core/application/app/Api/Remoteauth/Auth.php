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
        $token = filter_var(trim($this->_request->getParam('authorizationToken')), FILTER_SANITIZE_STRING);
        if (!empty($token) && mb_strlen($token) == 40) {
            $userMapper = Application_Model_Mappers_UserMapper::getInstance();
            $userModel = $userMapper->findByRemoteAuthToken($token);
            if ($userModel instanceof Application_Model_Models_User) {
                $allowRemoteAuth = $userModel->getAllowRemoteAuthorization();
                $redirector = new Zend_Controller_Action_Helper_Redirector();
                $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
                $websiteUrl = $websiteHelper->getUrl();
                $configHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
                $remoteLoginRedirect = $configHelper->getConfig('remoteLoginRedirect');
                $redirectTo = $websiteUrl;
                if (empty($allowRemoteAuth)) {
                    $redirector->gotoUrl($redirectTo);
                }
                $userModel->setRemoteAuthorizationToken('');
                $additionalParams = json_decode($userModel->getRemoteAuthorizationInfo(), true);
                $userModel->setPassword('');
                $userModel->setLastLogin(date(Tools_System_Tools::DATE_MYSQL));
                $userModel->setIpaddress($_SERVER['REMOTE_ADDR']);
                $sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
                $sessionHelper->setCurrentUser($userModel);
                $userMapper->save($userModel);
                $cacheHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('cache');
                $cacheHelper->clean();
                if (!empty($remoteLoginRedirect)) {
                    $redirectTo = $remoteLoginRedirect;
                } elseif (!empty($additionalParams['redirectLink'])) {
                    $redirectTo = $websiteUrl . $additionalParams['redirectLink'];
                }

                $redirector->gotoUrl($redirectTo);
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