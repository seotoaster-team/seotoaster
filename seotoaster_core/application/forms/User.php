<?php

/**
 * User
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Form_User extends Application_Form_Secure {

	protected $_email    = '';

	protected $_fullName = '';

	protected $_password = '';

	protected $_roleId   = '';

	protected $_id       = '';

    protected $_mobilePhone = '';

    protected $_timezone = '';

    protected $_mobileCountryCode = null;

    protected $_mobileCountryCodeValue = null;

    protected $_desktopPhone = null;

    protected $_desktopCountryCode = null;

    protected $_desktopCountryCodeValue = null;

    protected $_signature = null;

    protected $_subscribed = null;

    protected $_prefix = null;

    protected $_allowRemoteAuthorization = '0';

    protected $_personalCalendarUrl = null;

    protected $_avatarLink = null;

	public function init() {
        parent::init();

        $translator = Zend_Registry::get('Zend_Translate');

        $this->addElement(new Zend_Form_Element_Checkbox(array(
            'name'       => 'subscribed',
            'id'         => 'user-subscribed',
            'label'      => $translator->translate('Subscribe'),
            'required'   => false,
            'value'      => $this->_subscribed
        )));



        $this->addElement(new Zend_Form_Element_Checkbox(array(
            'name'       => 'allowRemoteAuthorization',
            'id'         => 'allow-remote-login',
            'label'      => $translator->translate('Allow remote login'),
            'required'   => false,
            'value'      => $this->_allowRemoteAuthorization
        )));


        $email = new Zend_Form_Element_Text(array(
            'id'         => 'e-mail',
            'name'       => 'email',
            'label'      => $translator->translate('E-mail'),
            'value'      => $this->_email,
            'validators' => array(
                new Tools_System_CustomEmailValidator(),
                new Zend_Validate_Db_NoRecordExists(array(
                    'table' => 'user',
                    'field' => 'email'
                ))
            ),
            'required'   => true,
            'filters'    => array('StringTrim')
        ));

        $this->addElement($email);

        $this->addElement(new Zend_Form_Element_Select(array(
            'name'         => 'prefix',
            'id'           => 'prefix',
            'label'        => $translator->translate('Prefix'),
            'value'        => $this->_prefix,
            'multiOptions' => array('' => $translator->translate('Select')) + Tools_System_Tools::getAllowedPrefixesList()
        )));

		$this->addElement(new Zend_Form_Element_Text(array(
			'name'       => 'fullName',
			'id'         => 'full-name',
			'label'      => $translator->translate('Full name'),
			'required'   => true,
			'validators' => array(
				new Zend_Validate_Alnum(array('allowWhiteSpace' => true)),
			),
			'value'      => $this->_fullName
		)));

		$this->addElement(new Zend_Form_Element_Password(array(
			'name'       => 'password',
			'id'         => 'password',
			'label'      => $translator->translate('Password'),
			'required'   => true,
			'validators' => array(
				new Zend_Validate_StringLength(array(
					'encoding' => 'UTF-8',
					'min'      => 4
				)),
			),
			//'placeholder' => '********',
			'value'      => $this->_password
		)));

		$acl = Zend_Registry::get('acl');
		$roles = array_filter($acl->getRoles(), function($role){
			return (($role !== Tools_Security_Acl::ROLE_SUPERADMIN) && $role !== Tools_Security_Acl::ROLE_GUEST);
		});

		$this->addElement(new Zend_Form_Element_Select(array(
			'name'         => 'roleId',
			'id'           => 'role-id',
			'label'        => $translator->translate('Role'),
			'value'        => $this->_roleId,
			'multiOptions' => array_combine($roles, array_map('ucfirst', $roles)),
			'required'     => true
		)));

        $this->addElement(new Zend_Form_Element_Text(array(
            'name'       => 'desktopPhone',
            'id'         => 'user-desktop-phone',
            'label'      => '',
            'value'      => $this->_desktopPhone,
            'placeholder' => $translator->translate('Desktop')
        )));

        $this->addElement(new Zend_Form_Element_Select(array(
            'name'         => 'mobileCountryCode',
            'id'           => 'user-mobile-country-code',
            'label'        => '',
            'value'        => $this->_mobileCountryCode,
            'multiOptions' => Tools_System_Tools::getFullCountryPhoneCodesList(true, array(), true),
            'class'        => 'mobile-phone-country-codes',
            'data-device-type'    => 'mobile'
        )));

        $this->addElement(new Zend_Form_Element_Select(array(
            'name'         => 'desktopCountryCode',
            'id'           => 'user-desktop-country-code',
            'label'        => '',
            'value'        => $this->_desktopCountryCode,
            'multiOptions' => Tools_System_Tools::getFullCountryPhoneCodesList(true, array(), true),
            'class'        => 'mobile-phone-country-codes',
            'data-device-type'    => 'desktop'
        )));

        $this->addElement(new Zend_Form_Element_Hidden(array(
            'name'       => 'mobileCountryCodeValue',
            'id'         => 'user-mobile-country-value',
            'label'      => '',
            'value'      => $this->_mobileCountryCodeValue
        )));

        $this->addElement(new Zend_Form_Element_Hidden(array(
            'name'       => 'desktopCountryCodeValue',
            'id'         => 'user-desktop-country-value',
            'label'      => '',
            'value'      => $this->_desktopCountryCodeValue
        )));

        $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
        array_pop($timezones);

        $this->addElement(new Zend_Form_Element_Select(
            array(
                'name' => 'timezone',
                'id' => 'user-timezone',
                'label' => $translator->translate('Timezone'),
                'multiOptions' => array('0' => $translator->translate('Select timezone')) + array_combine($timezones, $timezones)
            )
        ));

        $configHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
        $userDefaultTimezone = $configHelper->getConfig('userDefaultTimezone');
        $userDefaultPhoneMobileCode = $configHelper->getConfig('userDefaultPhoneMobileCode');
        if (!empty($userDefaultTimezone)) {
            $this->getElement('timezone')->setValue($userDefaultTimezone);
        }
        if (!empty($userDefaultPhoneMobileCode)) {
            $this->getElement('desktopCountryCode')->setValue($userDefaultPhoneMobileCode);
            $this->getElement('mobileCountryCode')->setValue($userDefaultPhoneMobileCode);
        }

        $this->addElement(new Zend_Form_Element_Textarea(array(
            'name'  => 'signature',
            'id'    => 'signature',
            'label' => $translator->translate('Signature'),
            'cols' => '15',
            'rows' => '4'
        )));

        $this->addElement(new Zend_Form_Element_Text(array(
            'name'  => 'voipPhone',
            'id'    => 'voip-phone',
            'label' => $translator->translate('VOIP phone'),
            'placeholder' => 'sip:username@sipdomain.com'
        )));

        $this->addElement(new Zend_Form_Element_Text(array(
            'name'  => 'gplusProfile',
            'id'    => 'gplus-profile',
            'label' => 'Google+ profile',
            'class' => 'hide'
        )));

        $this->addElement(new Zend_Form_Element_Text(array(
            'name'       => 'mobilePhone',
            'id'         => 'user-mobile-phone',
            'label'      => '',
            'value'      => $this->_mobilePhone,
            'placeholder' => $translator->translate('Mobile')
        )));

        $this->addElement(new Zend_Form_Element_Select(array(
            'name'  => 'userAttributes',
            'id'    => 'user-attributes',
            'value' => array(''),
            'multiOptions' => $this->getUniqueAttributesNames(),
            'label' => $translator->translate('User attributes')
        )));


        $this->addElement(new Zend_Form_Element_Select(array(
            'name'         => 'roleId',
            'id'           => 'role-id',
            'label'        => $translator->translate('Role'),
            'value'        => $this->_roleId,
            'multiOptions' => array_combine($roles, array_map('ucfirst', $roles)),
            'required'     => true
        )));

        $this->addElement(new Zend_Form_Element_Text(array(
            'name'  => 'personalCalendarUrl',
            'id'    => 'personal-calendar-url',
            'label' => $translator->translate('Personal calendar url')
        )));

        $this->addElement(new Zend_Form_Element_Text(array(
            'name'  => 'avatarLink',
            'id'    => 'avatar-link',
            'label' => $translator->translate('Avatar link')
        )));

		$this->addElement(new Zend_Form_Element_Hidden(array(
			'id'    => 'user-id',
			'name'  => 'id',
			'value' => $this->_id
		)));

		$this->addElement(new Zend_Form_Element_Submit(array(
			'name'   => 'saveUser',
			'id'     => 'save-user',
			'value'  => $translator->translate('Save user'),
			'class'  => 'btn',
			'ignore' => true,
			'label'  => $translator->translate('Save user'),
			'escape' => false
		)));


		$this->setElementDecorators(array('ViewHelper', 'Label'));
		$this->getElement('saveUser')->removeDecorator('Label');
	}

	public function getEmail() {
		return $this->_email;
	}

	public function setEmail($email) {
		$this->_email = $email;
		$this->getElement('email')->setValue($email);
		return $this;
	}

	public function getFullName() {
		return $this->_fullName;
	}

	public function setFullName($fullName) {
		$this->_fullName = $fullName;
		$this->getElement('fullName')->setValue($fullName);
		return $this;
	}

	public function getPassword() {
		return $this->_password;
	}

	public function setPassword($password) {
		$this->_password = $password;
		$this->getElement('password')->setValue($password);
		return $this;
	}

	public function getRoleId() {
		return $this->_roleId;
	}

	public function setRoleId($roleId) {
		$this->_roleId = $roleId;
		$this->getElement('roleId')->setValue($roleId);
		return $this;
	}

    public function getUniqueAttributesNames() {
        $userMapper = Application_Model_Mappers_UserMapper::getInstance();
        $attributes = $userMapper->fetchUniqueAttributesNames();
        array_unshift($attributes, 'Select user attribute');
        return $attributes;

    }

	public function getId() {
		return $this->_id;
	}

	public function setId($id) {
		$this->_id = $id;
		$this->getElement('id')->setValue($id);
        $this->getElement('email')->removeValidator('Zend_Validate_Db_NoRecordExists');
		return $this;
	}

    public function getMobilePhone()
    {
        return $this->_mobilePhone;
    }

    public function setMobilePhone($mobilePhone)
    {
        $this->_mobilePhone = $mobilePhone;
        $this->getElement('mobilePhone')->setValue($mobilePhone);
        return $this;
    }

    public function getTimezone()
    {
        return $this->_timezone;
    }

    public function setTimezone($timezone)
    {
        if (empty($timezone)) {
            $timezone = '0';
        }
        $this->getElement('timezone')->setValue($timezone);
        return $this;
    }

    public function getMobileCountryCode()
    {
        return $this->_mobilePhone;
    }

    public function setMobileCountryCode($mobileCountryCode)
    {
        $this->_mobileCountryCode = $mobileCountryCode;
        $this->getElement('mobileCountryCode')->setValue($mobileCountryCode);
        return $this;
    }

    public function getDesktopCountryCode()
    {
        return $this->_desktopCountryCode;
    }

    public function setDesktopCountryCode($desktopCountryCode)
    {
        $this->_desktopCountryCode = $desktopCountryCode;
        $this->getElement('desktopCountryCode')->setValue($desktopCountryCode);
        return $this;
    }

    public function getMobileCountryCodeValue()
    {
        return $this->_mobileCountryCodeValue;
    }

    public function setMobileCountryCodeValue($mobileCountryCodeValue)
    {
        $this->_mobileCountryCodeValue = $mobileCountryCodeValue;
        $this->getElement('mobileCountryCodeValue')->setValue($mobileCountryCodeValue);
        return $this;
    }

    public function getDesktopPhone()
    {
        return $this->_desktopPhone;
    }

    public function setDesktopPhone($desktopPhone)
    {
        $this->_desktopPhone = $desktopPhone;
        $this->getElement('desktopPhone')->setValue($desktopPhone);
        return $this;
    }

    public function getDesktopCountryCodeValue()
    {
        return $this->_desktopCountryCodeValue;
    }

    public function setDesktopCountryCodeValue($desktopCountryCodeValue)
    {
        $this->_desktopCountryCodeValue = $desktopCountryCodeValue;
        $this->getElement('desktopCountryCodeValue')->setValue($desktopCountryCodeValue);
        return $this;
    }

    public function getSignature()
    {
        return $this->_signature;
    }

    public function setSignature($signature)
    {
        $this->_signature = $signature;
        $this->getElement('signature')->setValue($signature);
        return $this;
    }


    public function getSubscribed()
    {
        return $this->_subscribed;
    }

    public function setSubscribed($subscribed)
    {
        $this->_subscribed = $subscribed;
        $this->getElement('subscribed')->setValue($subscribed);
        return $this;
    }

    public function getPrefix() {
        return $this->_prefix;
    }

    public function setPrefix($prefix) {
        $this->_prefix = $prefix;
        $this->getElement('prefix')->setValue($prefix);
        return $this;
    }


    public function getAllowRemoteAuthorization() {
        return $this->_allowRemoteAuthorization;
    }

    public function setAllowRemoteAuthorization($allowRemoteAuthorization) {
        $this->_allowRemoteAuthorization = $allowRemoteAuthorization;
        $this->getElement('allowRemoteAuthorization')->setValue($allowRemoteAuthorization);
        return $this;
    }

    public function getPersonalCalendarUrl()
    {
        return $this->_personalCalendarUrl;
    }

    public function setPersonalCalendarUrl($personalCalendarUrl)
    {
        $this->_personalCalendarUrl = $personalCalendarUrl;
        $this->getElement('personalCalendarUrl')->setValue($personalCalendarUrl);
        return $this;
    }

    public function getAvatarLink()
    {
        return $this->_avatarLink;
    }

    public function setAvatarLink($avatarLink)
    {
        $this->_avatarLink = $avatarLink;
        $this->getElement('avatarLink')->setValue($avatarLink);
        return $this;
    }

}

