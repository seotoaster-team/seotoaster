<?php
class MagicSpaces_Notloggedin_Notloggedin extends Tools_MagicSpaces_Abstract
{
	protected $_parseBefore = true;

	protected function _run()
	{
        $sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
        $loggedUser = $sessionHelper->getCurrentUser();
        $userRole = $loggedUser->getRoleId();
        if ($userRole === Tools_Security_Acl::ROLE_GUEST) {
            return $this->_spaceContent;
        }

        return '';
	}
}
