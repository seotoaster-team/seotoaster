<?php

class MagicSpaces_Remoteauthorization_Remoteauthorization extends Tools_MagicSpaces_Abstract
{

    /**
     * Parse before widgets
     *
     * @var bool
     */
    protected $_parseBefore = true;

    protected function _run()
    {

        $session = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
        $currentUser = $session->getCurrentUser();
        $userId = $currentUser->getId();

        $usersMapper = Application_Model_Mappers_UserMapper::getInstance();
        $userModel = $usersMapper->find($userId);
        if (!$userModel instanceof Application_Model_Models_User) {
            return '';
        } else {
            $allowed = $userModel->getAllowRemoteAuthorization();
            if (!empty($allowed)) {
                return $this->_spaceContent;
            }
            return '';
        }
    }

}