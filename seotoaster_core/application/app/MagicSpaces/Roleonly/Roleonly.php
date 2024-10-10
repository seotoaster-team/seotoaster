<?php

class MagicSpaces_Roleonly_Roleonly extends Tools_MagicSpaces_Abstract
{
    protected $_parseBefore = true;

    /**
     * Role only Magic Space
     * {roleonly[:role1,role2,...]}
     * {/roleonly}
     * @return string
     */
    protected function _run()
    {
        $allowedRoles = explode(',', filter_var($this->_params[0], FILTER_SANITIZE_STRING));

        $sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
        $user = $sessionHelper->getCurrentUser();
        $roleId = $user->getRoleId();
        if (!in_array($roleId, $allowedRoles)) {
            return '';
        }


        return $this->_spaceContent;
    }
}
