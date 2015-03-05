<?php

/**
 * Config
 *
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Application_Form_Config extends Application_Form_Secure
{
	private $_name = 'configForm';

	protected $_currentTheme;
	protected $_imgSmall;
	protected $_imgMedium;
	protected $_imgLarge;
	protected $_teaserSize = '';
	protected $_adminEmail;
	protected $_useSmtp;
	protected $_smtpHost;
	protected $_smtpLogin;
	protected $_smtpPassword;
	protected $_smtpPort;
	protected $_smtpSsl;
	protected $_language;
	protected $_suLogin;
	protected $_suPassword;
	protected $_mediaServers;
	protected $_inlineEditor;
	protected $_canonicalScheme;
    protected $_recaptchaPublicKey;
    protected $_recaptchaPrivateKey;

	/**
	 * Wether or not to include protected pages into the menus
	 *
	 * @var boolean
	 */
	protected $_showProtectedPagesInMenu = true;

    /**
     * Enable minification css and js files
     *
     * @var boolean
     */
    protected $_enableMinify             = false;

     /**
     * Enable Developer mode
     *
     * @var boolean
     */
    protected $_enableDeveloperMode      = false;

    /**
     *  Show or hide control panel from members
     * @var bool
     */
    protected $_controlPanelStatus       = false;

	public function getMediaServers() {
		return $this->_mediaServers;
	}

	public function setMediaServers($_mediaServers)
    {
		$this->_mediaServers = $_mediaServers;
		$this->getElement('mediaServers')->setValue($this->_mediaServers);
	}

	public function getCurrentTheme()
    {
		return $this->_currentTheme;
	}

	public function setCurrentTheme($_currentTheme)
    {
		$this->_currentTheme = $_currentTheme;
		$this->getElement('currentTheme')->setValue($this->_currentTheme);
	}

	public function getImgSmall()
    {
		return $this->_imgSmall;
	}

	public function setImgSmall($_imgSmall)
    {
		$this->_imgSmall = $_imgSmall;
		$this->getElement('imgSmall')->setValue($this->_imgSmall);
	}

	public function getImgMedium()
    {
		return $this->_imgMedium;
	}

	public function setImgMedium($_imgMedium)
    {
		$this->_imgMedium = $_imgMedium;
		$this->getElement('imgMedium')->setValue($this->_imgMedium);
	}

	public function getImgLarge()
    {
		return $this->_imgLarge;
	}

	public function setImgLarge($_imgLarge)
    {
		$this->_imgLarge = $_imgLarge;
		$this->getElement('imgLarge')->setValue($this->_imgLarge);
	}

	/*public function getAdminEmail()
	{
		return $this->_adminEmail;
	}

	public function setAdminEmail($_adminEmail)
	{
		$this->_adminEmail = $_adminEmail;
		$this->getElement('adminEmail')->setValue($this->_adminEmail);
	}*/

	public function getUseSmtp()
    {
		return $this->_useSmtp;
	}

	public function setUseSmtp($_useSmtp)
    {
		$this->_useSmtp = $_useSmtp;
		$this->getElement('useSmtp')->setValue($this->_useSmtp);
	}

	public function getSmtpHost()
    {
		return $this->_smtpHost;
	}

	public function setSmtpHost($_smtpHost)
    {
		$this->_smtpHost = $_smtpHost;
		$this->getElement('smtpHost')->setValue($this->_smtpHost);
	}

	public function getSmtpLogin()
    {
		return $this->_smtpLogin;
	}

	public function setSmtpLogin($_smtpLogin)
    {
		$this->_smtpLogin = $_smtpLogin;
		$this->getElement('smtpLogin')->setValue($this->_smtpLogin);
	}

	public function getSmtpPassword()
    {
		return $this->_smtpPassword;
	}

	public function setSmtpPassword($_smtpPassword)
    {
		$this->_smtpPassword = $_smtpPassword;
		$this->getElement('smtpPassword')->setValue($this->_smtpPassword);
	}

	public function getLanguage()
    {
		return $this->_language;
	}

	public function setLanguage($_language)
    {
		$this->_language = $_language;
		$this->getElement('language')->setValue($this->_language);
	}

	public function getSuLogin()
    {
		return $this->_suLogin;
	}

	public function setSuLogin($_suLogin)
    {
		$this->_suLogin = $_suLogin;
		$this->getElement('suLogin')->setValue($this->_suLogin);
	}

	public function getSuPassword()
    {
		return $this->_suPassword;
	}

	public function setSuPassword($_suPassword)
    {
		$this->_suPassword = $_suPassword;
		$this->getElement('suPassword')->setValue($this->_suPassword);
	}

	public function getTeaserSize()
    {
		return $this->_teaserSize;
	}

	public function setTeaserSize($teaserSize)
    {
		$this->_teaserSize = $teaserSize;
		$this->getElement('teaserSize')->setValue($this->_teaserSize);
		return $this;
	}

	public function getMemPagesInMenu()
    {
		return $this->_showProtectedPagesInMenu;
	}

	public function setMemPagesInMenu($showProtectedPagesInMenu)
    {
		$this->_showProtectedPagesInMenu = $showProtectedPagesInMenu;
        $this->getElement('memPagesInMenu')->setValue($showProtectedPagesInMenu);
		return $this;
	}

    public function getEnableMinify()
    {
        return $this->_enableMinify;
    }

    public function setEnableMinify($enableMinify)
    {
        $this->_enableMinify = $enableMinify;
        $this->getElement('enableMinify')->setValue($enableMinify);
    }

    public function getControlPanelStatus()
    {
        return $this->_controlPanelStatus;
    }

    public function setControlPanelStatus($controlPanelStatus)
    {
        $this->_controlPanelStatus = $controlPanelStatus;
        $this->getElement('controlPanelStatus')->setValue($controlPanelStatus);
        return $this;
    }

    public function getEnableDeveloperMode()
    {
        return $this->_enableDeveloperMode;
    }

    public function setEnableDeveloperMode($enableDeveloperMode)
    {
        $this->_enableDeveloperMode = $enableDeveloperMode;
        $this->getElement('enableDeveloperMode')->setValue($enableDeveloperMode);
        return $this;
    }

	public function setSmtpPort($smtpPort)
    {
		$this->_smtpPort = $smtpPort;
		$this->getElement('smtpPort')->setValue($smtpPort);
		return $this;
	}

	public function getSmtpPort()
    {
		return $this->_smtpPort;
	}

	public function setSmtpSsl($smtpSsl)
    {
		$this->_smtpSsl = $smtpSsl;
		$this->getElement('smtpSsl')->setValue($smtpSsl);
		return $this;
	}

	public function getSmtpSsl()
    {
		return $this->_smtpSsl;
	}

    public function setRecaptchaPublicKey($recaptchaPublicKey)
    {
		$this->_recaptchaPublicKey = $recaptchaPublicKey;
		$this->getElement(Tools_System_Tools::RECAPTCHA_PUBLIC_KEY)->setValue($recaptchaPublicKey);
		return $this;
	}

	public function getRecaptchaPublicKey()
    {
		return $this->_recaptchaPublicKey;
	}

    public function setRecaptchaPrivateKey($recaptchaPrivateKey)
    {
		$this->_recaptchaPrivateKey = $recaptchaPrivateKey;
		$this->getElement(Tools_System_Tools::RECAPTCHA_PRIVATE_KEY)->setValue($recaptchaPrivateKey);
		return $this;
	}

	public function getRecaptchaPrivateKey()
    {
		return $this->_recaptchaPrivateKey;
	}

	public function init()
    {
        parent::init();
		$this->setName($this->_name)
			->setMethod(Zend_FORM::METHOD_POST)
			->setDecorators(array(
				'FormElements',
				'Form'
				))
			->setElementDecorators(array(
				'ViewHelper',
				'Label',
				new Zend_Form_Decorator_HtmlTag(array('tag' => 'div', 'class' => 'grid_12 mt10px'))
				))
			->setElementFilters(array('StringTrim', 'StripTags'));

		$this->addElement('hidden', 'currentTheme', array(
			'value'		=> $this->_currentTheme,
			//'label'		=> 'Current Theme',
			'readonly'	=> true
		));

		/*$this->addElement('text', 'adminEmail', array(
			'value'	=> $this->_adminEmail,
			'label' => 'Admin Email',
			'validators' => array(new Zend_Validate_EmailAddress())
		));*/

        $this->addElement('text', Tools_System_Tools::RECAPTCHA_PUBLIC_KEY, array(
			'value' => $this->_recaptchaPublicKey,
			'label' => 'reCAPTCHA public key'
		));

        $this->addElement('text', Tools_System_Tools::RECAPTCHA_PRIVATE_KEY, array(
			'value' => $this->_recaptchaPrivateKey,
			'label' => 'reCAPTCHA private Key'
		));

		$this->addElement('text', 'imgSmall', array(
			'value' => $this->_imgSmall,
			'title' => 'Small Image Size',
            'class' => 'grid_4 alpha',
            'validators' => array(new Zend_Validate_Int())
		));
		$this->addElement('text', 'imgMedium', array(
			'value' => $this->_imgMedium,
			'title' => 'Medium Image Size',
            'class' => 'grid_4',
			'validators' => array(new Zend_Validate_Int())
		));
		$this->addElement('text', 'imgLarge', array(
			'value' => $this->_imgLarge,
			'title' => 'Large Image Size',
            'class' => 'grid_4 omega',
			'validators' => array(new Zend_Validate_Int())
		));

		$this->addElement('text', 'teaserSize', array(
			'value' => $this->_teaserSize,
			'label' => 'Page Teaser Image Size',
			'validators' => array(new Zend_Validate_Int())
		));

		$this->addElement('checkbox', 'useSmtp', array(
			'value' => $this->_useSmtp,
			'label' => 'Use SMTP?'
		));
		$this->addElement('text', 'smtpHost', array(
			'value' => $this->_smtpHost,
			'label' => 'SMTP Hostname',
			'placeholder' => 'e.g., smtp.gmail.com'
		));
		$this->addElement('text', 'smtpLogin', array(
			'value' => $this->_smtpLogin,
			'label' => 'SMTP Login',
            'autocomplete' => 'off'
		));
		$this->addElement('password', 'smtpPassword', array(
			'value'  => $this->_smtpPassword,
			'label'  => 'SMTP Password',
            'autocomplete' => 'off',
			'renderPassword' => Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_USERS)
		));

		$this->addElement('text', 'smtpPort', array(
			'value' => $this->_smtpPort,
			'class' => 'optional',
			'label' => 'SMTP Port',
			'placeholder' => 'empty by default',
			'validators' => array(new Zend_Validate_Digits())
		));

		$this->addElement('select', 'smtpSsl', array(
			'label' => 'SSL',
			'class' => 'optional',
			'multiOptions' => array(
				0 => 'no',
				'ssl'  => 'SSL',
				'tls'  => 'TLS'
			)
		));

		$this->addElement('select', 'language', array(
			'value' => $this->_language,
			'label' => 'Website Language'
//			'ignore' => true
		));

		$this->addElement('text', 'suLogin', array(
			'value' => $this->_suLogin,
			'label' => 'E-mail',
			'validators' => array(new Zend_Validate_EmailAddress()),
			'ignore' => true
		));

		$this->addElement('password', 'suPassword', array(
			'value' => $this->_suPassword,
			'label' => 'Password',
			'validators' => array(array('StringLength', true, array(4))),
			'ignore' => true,
			'placeholder' => '*******'
		));

		$this->addElement(new Zend_Form_Element_Checkbox(array(
			'name'  => 'memPagesInMenu',
			'value' => $this->_showProtectedPagesInMenu,
			'label' => 'Member pages in menu?'
		)));

        $this->addElement(new Zend_Form_Element_Checkbox(array(
            'name'  => 'enableMinify',
            'value' => $this->_enableMinify,
            'label' => 'Enable assets minification (css/js)?'
        )));

        $this->addElement(new Zend_Form_Element_Checkbox(array(
            'name'  => 'controlPanelStatus',
            'value' => $this->_controlPanelStatus,
            'label' => 'Hide Control Panel from members?'
        )));

        $this->addElement(new Zend_Form_Element_Checkbox(array(
            'name'  => 'enableDeveloperMode',
            'value' => $this->_enableDeveloperMode,
            'label' => 'Enable developer mode?'
        )));

		$this->addElement(new Zend_Form_Element_Button(array(
			'name'  => 'submit',
			'type'  => 'submit',
			'label' => 'Save',
            'class' => 'btn ticon-save mr-grid',
            'ignore' => true,
			'escape'=> false
		)));

		$this->addElement('checkbox', 'mediaServers', array(
			'value' => $this->_mediaServers,
			'label' => 'Use mediaServers?'
		));

		$this->addElement('checkbox', 'inlineEditor', array(
			'value' => $this->_inlineEditor,
			'label' => 'Enable edit zones highlighting?'
		));

		$this->addElement('select', 'canonicalScheme', array(
			'value' => $this->_canonicalScheme,
			'label' => 'Set canonicalization tag to',
			'multiOptions' => array(
				Zend_Controller_Request_Http::SCHEME_HTTP => 'http',
				Zend_Controller_Request_Http::SCHEME_HTTPS => 'https'
			)
		));

        $this->setElementDecorators(array('ViewHelper', 'Label'));
	}

	public function proccessErrors()
    {
		$errors = $this->getErrors();
		$isAnyErrors = false;
		foreach ($errors as $element => $errorsArray) {
			if (!empty ($errorsArray)){
				$this->getElement($element)->setAttrib('class', 'errors');
				$isAnyErrors = true;
			}
		}

		return $isAnyErrors;
	}

	public function setInlineEditor($inlineEditor)
    {
		$this->_inlineEditor = $inlineEditor;
		$this->getElement('inlineEditor')->setValue($this->_inlineEditor);
		return $this;
	}

	public function getInlineEditor()
    {
		return $this->_inlineEditor;
	}

	public function setCanonicalScheme($canonicalScheme)
    {
		$this->_canonicalScheme = $canonicalScheme;
		return $this;
	}

	public function getCanonicalScheme()
    {
		return $this->_canonicalScheme;
	}
}
