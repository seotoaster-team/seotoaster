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
}

