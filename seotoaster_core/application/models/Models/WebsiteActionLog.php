<?php

class Application_Model_Models_WebsiteActionLog extends Application_Model_Models_Abstract
{

    const ACTION_TYPE_FORM = 'form';

    const ACTION_TYPE_REGISTRATION = 'registration';

    protected $_createdAt = '';

    protected $_ipAddress = '';

    protected $_email = '';

    protected $_actionType = '';

    protected $_name = '';

    protected $_browserFingerprint = '';

    protected $_rawData = '';

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
    public function getEmail()
    {
        return $this->_email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->_email = $email;
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
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * @return string
     */
    public function getBrowserFingerprint()
    {
        return $this->_browserFingerprint;
    }

    /**
     * @param string $browserFingerprint
     */
    public function setBrowserFingerprint($browserFingerprint)
    {
        $this->_browserFingerprint = $browserFingerprint;
    }

    /**
     * @return string
     */
    public function getRawData()
    {
        return $this->_rawData;
    }

    /**
     * @param string $rawData
     */
    public function setRawData($rawData)
    {
        $this->_rawData = $rawData;
    }


}

