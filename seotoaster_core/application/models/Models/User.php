<?php

class Application_Model_Models_User extends Application_Model_Models_Abstract implements Zend_Acl_Role_Interface {

    protected $_email        = '';

	protected $_password     = '';

	protected $_roleId       = '';

	protected $_fullName     = '';

	protected $_lastLogin    = null;

	protected $_regDate      = '';

	protected $_ipaddress    = '';

	protected $_referer      = '';

    protected $_gplusProfile = '';

    protected $_attributes;

    protected $_mobilePhone = '';

    protected $_notes = '';

    protected $_timezone = '';

    protected $_mobileCountryCode = '';

    protected $_mobileCountryCodeValue = '';

    protected $_desktopPhone = '';

    protected $_desktopCountryCode = '';

    protected $_desktopCountryCodeValue = '';

    protected $_signature = '';

    public function setGplusProfile($gplusProfile) {
        $this->_gplusProfile = $gplusProfile;
        return $this;
    }

    public function getGplusProfile() {
        return $this->_gplusProfile;
    }

	public function getRoleId() {
		return ($this->_roleId) ? $this->_roleId : Tools_Security_Acl::ROLE_GUEST;
	}

	public function setRoleId($roleId) {
		$this->_roleId = $roleId;
		return $this;
	}

	public function getEmail() {
		return $this->_email;
	}

	public function setEmail($email) {
		$this->_email = $email;
		return $this;
	}

	public function getPassword() {
		return $this->_password;
	}

	public function setPassword($password) {
		$this->_password = $password;
		return $this;
	}

	public function getFullName() {
		return $this->_fullName;
	}

	public function setFullName($fullName) {
		$this->_fullName = $fullName;
		return $this;
	}

	public function getLastLogin() {
		return $this->_lastLogin;
	}

	public function setLastLogin($lastLogin) {
		$this->_lastLogin = $lastLogin;
		return $this;
	}

	public function getRegDate() {
		return $this->_regDate;
	}

	public function setRegDate($regDate) {
		$this->_regDate = $regDate;
		return $this;
	}

	public function getIpaddress() {
		return $this->_ipaddress;
	}

	public function setIpaddress($ipaddress) {
		$this->_ipaddress = $ipaddress;
		return $this;
	}

	public function setReferer($referer) {
		$this->_referer = $referer;
		return $this;
	}

	public function getReferer() {
		return $this->_referer;
	}

    public function setMobilePhone($mobilePhone)
    {
        $this->_mobilePhone = $mobilePhone;
        return $this;
    }

    public function getMobilePhone()
    {
        return $this->_mobilePhone;
    }

    /**
     * @return string
     */
    public function getNotes()
    {
        return $this->_notes;
    }

    /**
     * @param string $notes
     * @return string
     */
    public function setNotes($notes)
    {
        $this->_notes = $notes;

        return $this;
    }


    /**
     * @param array $attributes
     */
    public function setAttributes(array $attributes) {
        $this->_attributes = $attributes;
        return $this;
    }

    /**
     * @return array
     */
    public function getAttributes() {
        if (is_null($this->_attributes)){
            $this->loadAttributes();
        }
        return $this->_attributes;
    }

    public function setAttribute($name, $value) {
        $this->_attributes[$name] = $value;
        return $this;
    }

    /**
     * @param $name
     * @return array
     */
    public function getAttribute($name) {
        if (is_null($this->_attributes)){
            $this->loadAttributes();
        }
        if ($this->hasAttribute($name)){
            return $this->_attributes[$name];
        }
    }

    /**
     * Checks if the attribute exists
     * @param $name
     * @return bool
     */
    public function hasAttribute($name) {
        return array_key_exists($name, $this->_attributes);
    }

    /**
     * Loads extended attributes to user model
     * @return Application_Model_Models_User
     */
    public function loadAttributes() {
        return Application_Model_Mappers_UserMapper::getInstance()->loadUserAttributes($this);
    }

    /**
     * @return string
     */
    public function getTimezone()
    {
        return $this->_timezone;
    }

    /**
     * @param string $timezone
     * @return string
     */
    public function setTimezone($timezone)
    {
        if (empty($timezone)) {
            $timezone = null;
        }
        $this->_timezone = $timezone;

        return $this;
    }

    /**
     * @return string
     */
    public function getMobileCountryCode()
    {
        return $this->_mobileCountryCode;
    }

    /**
     * @param string $mobileCountryCode
     * @return string
     */
    public function setMobileCountryCode($mobileCountryCode)
    {
        $this->_mobileCountryCode = $mobileCountryCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getMobileCountryCodeValue()
    {
        return $this->_mobileCountryCodeValue;
    }

    /**
     * @param string $mobileCountryCodeValue
     * @return string
     */
    public function setMobileCountryCodeValue($mobileCountryCodeValue)
    {
        $this->_mobileCountryCodeValue = $mobileCountryCodeValue;

        return $this;
    }

    /**
     * @return string
     */
    public function getDesktopPhone()
    {
        return $this->_desktopPhone;
    }

    /**
     * @param string $desktopPhone
     * @return string
     */
    public function setDesktopPhone($desktopPhone)
    {
        $this->_desktopPhone = $desktopPhone;

        return $this;
    }

    /**
     * @return string
     */
    public function getDesktopCountryCode()
    {
        return $this->_desktopCountryCode;
    }

    /**
     * @param string $desktopCountryCode
     * @return string
     */
    public function setDesktopCountryCode($desktopCountryCode)
    {
        $this->_desktopCountryCode = $desktopCountryCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getDesktopCountryCodeValue()
    {
        return $this->_desktopCountryCodeValue;
    }

    /**
     * @param string $desktopCountryCodeValue
     * @return string
     */
    public function setDesktopCountryCodeValue($desktopCountryCodeValue)
    {
        $this->_desktopCountryCodeValue = $desktopCountryCodeValue;

        return $this;
    }

    /**
     * @return string
     */
    public function getSignature()
    {
        return $this->_signature;
    }

    /**
     * @param string $signature
     * @return string
     */
    public function setSignature($signature)
    {
        $this->_signature = $signature;

        return $this;
    }

}

