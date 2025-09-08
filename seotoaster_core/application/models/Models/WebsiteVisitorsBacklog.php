<?php

class Application_Model_Models_WebsiteVisitorsBacklog extends Application_Model_Models_Abstract
{

    const ACTION_TYPE_COOLDOWN = 'cooldown';

    protected $_createdAt = '';

    protected $_ipAddress = '';

    protected $_lastActionId = '';

    protected $_actionType = '';

    protected $_reason = '';

    protected $_validUntil = '';

    /**
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->_createdAt;
    }

    /**
     * @param string $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->_createdAt = $createdAt;
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

    /**
     * @return string
     */
    public function getLastActionId()
    {
        return $this->_lastActionId;
    }

    /**
     * @param string $lastActionId
     */
    public function setLastActionId($lastActionId)
    {
        $this->_lastActionId = $lastActionId;
    }

    /**
     * @return string
     */
    public function getActionType()
    {
        return $this->_actionType;
    }

    /**
     * @param string $actionType
     */
    public function setActionType($actionType)
    {
        $this->_actionType = $actionType;
    }

    /**
     * @return string
     */
    public function getReason()
    {
        return $this->_reason;
    }

    /**
     * @param string $reason
     */
    public function setReason($reason)
    {
        $this->_reason = $reason;
    }

    /**
     * @return string
     */
    public function getValidUntil()
    {
        return $this->_validUntil;
    }

    /**
     * @param string $validUntil
     */
    public function setValidUntil($validUntil)
    {
        $this->_validUntil = $validUntil;
    }

}

