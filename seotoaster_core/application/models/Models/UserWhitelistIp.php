<?php

class Application_Model_Models_UserWhitelistIp extends Application_Model_Models_Abstract
{

    protected $_roleId = '';

    protected $_ipAddress = '';

    /**
     * @return string
     */
    public function getRoleId()
    {
        return $this->_roleId;
    }

    /**
     * @param string $roleId
     */
    public function setRoleId($roleId)
    {
        $this->_roleId = $roleId;
    }

    /**
     * @return string
     */
    public function getIpAddress()
    {
        return $this->_ipAddress;
    }

    /**
     * @param string $ipAddress
     */
    public function setIpAddress($ipAddress)
    {
        $this->_ipAddress = $ipAddress;
    }




}

