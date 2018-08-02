<?php

class Widgets_Member_Member extends Widgets_Abstract {

	protected $_session        = null;

    protected $_website        = null;

    protected $_flashMessanger = null;

    protected $_cacheable      = false;

    protected $_translator      = false;

    /**
     * Fields names that must be always present on the member form
     *
     * @var array
     */
    public static $_formMandatoryFields = array(
        'email'    => true,
        'password' => true,
        'prefix'   => false,
        'fullName' => true,
        'saveUser' => false
    );

    /**
     * Fields to remove to keep backward compatibility
     *
     * @var array
     */
    public static $_oldCompatibilityFields = array(
        'mobileCountryCode',
        'desktopCountryCode',
        'timezone',
        'gplusProfile',
        'mobilePhone',
        'desktopPhone',
        'signature',
        'subscribed',
        'voipPhone'
    );

    const  OPTION_NOCAPTCHA = 'nocaptcha';

	protected function  _init() {
		parent::_init();
		$this->_view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));
		$this->_website          = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$this->_view->websiteUrl = $this->_website->getUrl();
		$this->_session          = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
        $this->_flashMessanger   = Zend_Controller_Action_HelperBroker::getStaticHelper('flashMessenger');
	}

	protected function  _load() {
		$option       = array_shift($this->_options);
		$rendererName = '_renderMember' . ucfirst($option);
		if(method_exists($this, $rendererName)) {
			return $this->$rendererName($this->_options);
		}
		throw new Exceptions_SeotoasterWidgetException($this->_translator->translate('Wrong member type'));
	}

	protected function _renderMemberLogin() {
		$this->_view->userRole  = $this->_session->getCurrentUser()->getRoleId();
		$loginForm = new Application_Form_Login();
		$this->_view->secureToken = Tools_System_Tools::initSecureToken(Tools_System_Tools::ACTION_PREFIX_LOGIN);
		$this->_view->loginForm = $this->_reInitDecorators($loginForm);
        $this->_view->messages  = array_merge($this->_flashMessanger->getMessages('passreset'), $this->_flashMessanger->getMessages());
        $this->_flashMessanger->clearMessages('passreset');
        $passwordRetrieveFrom = new Application_Form_PasswordRetrieve();
        $passwordRetrieveFrom->setAction($this->_website->getUrl().'login/retrieve');
        $this->_view->retrieveForm = $passwordRetrieveFrom;
        $this->_session->retrieveRedirect = $this->_toasterOptions['url'];

        unset($this->_session->errMemeberLogin);
		if(isset($this->_options[0])) {
			$this->_session->redirectUserTo = $this->_options[0];
		}
		return $this->_view->render('login.phtml');
	}

    protected function _renderMemberLogout() {
        if($this->_session->getCurrentUser()->getRoleId() == Tools_Security_Acl::ROLE_GUEST){
            return '';
        }
        $translator = Zend_Registry::get('Zend_Translate');
        return '<a href="' . $this->_website->getUrl() . 'logout" class="logout">' . $translator->translate('Logout') . '</a>';
	}


    protected function _renderMemberSignup() {
        $pageId = $this->_toasterOptions['id'];
        $signupForm = new Application_Form_Signup();
        $signupForm->addElement('hidden','PageId',array(
                'value' => $pageId,
                'id'    => 'PageId'));

        $noCaptchaOption = array_search(self::OPTION_NOCAPTCHA, $this->_options);
        if ($noCaptchaOption !== false) {
            $signupForm->removeElement('verification');
            $signupForm->addElement('hidden','token',array(
                    'value' => '',
                    'id'    => 'token'));
            $key = md5('signup'.$pageId);
            $this->_session->$key = $key;
            unset($this->_options[$noCaptchaOption]);
        }

        $options = array();
        $fieldsOptions = preg_grep('~formFields-~ui', $this->_options);
        if (!empty($fieldsOptions)) {
            $options = explode(',' ,str_replace('formFields-', '', array_pop($fieldsOptions)));
        }

        if (empty($options)) {
            foreach (self::$_oldCompatibilityFields as $field) {
                $signupForm->removeElement($field);
            }
        }

        $signupFormKeyParams = 'signUpKeyParams'.$pageId;
        $this->_session->$signupFormKeyParams = $options;

        $signupForm = Tools_System_Tools::adjustFormFields($signupForm, $options, self::$_formMandatoryFields);

        $this->_view->signupForm = $signupForm;

        $mobileEl = $signupForm->getElement('mobilePhone');
        $mobileCountryCodeEl = $signupForm->getElement('mobileCountryCode');
        $desktopPhoneEl = $signupForm->getElement('desktopPhone');
        $desktopCountryCodeEl = $signupForm->getElement('desktopCountryCode');

        if (!empty($mobileEl) && !empty($mobileCountryCodeEl)) {
            $signupForm->getElement('mobilePhone')->setLabel(null);
            $signupForm->getElement('mobileCountryCode')->setLabel('Mobile');
            $this->_view->withMobileMask = true;
        }

        if (!empty($desktopPhoneEl) && !empty($desktopCountryCodeEl)) {
            $signupForm->getElement('desktopPhone')->setLabel(null);
            $signupForm->getElement('desktopCountryCode')->setLabel('Phone');
            $this->_view->withDesktopMask = true;
        }

        $listMasksMapper = Application_Model_Mappers_MasksListMapper::getInstance();
        $this->_view->mobileMasks = $listMasksMapper->getListOfMasksByType(Application_Model_Models_MaskList::MASK_TYPE_MOBILE);
        $this->_view->desktopMasks = $listMasksMapper->getListOfMasksByType(Application_Model_Models_MaskList::MASK_TYPE_DESKTOP);

		$flashMessenger                = Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger');
		$errorMessages                 = $flashMessenger->getMessages();
		$this->_session->signupPageUrl = $this->_toasterOptions['url'];
		$this->_view->errors           = ($errorMessages) ? $errorMessages : null;
		return $this->_view->render('signup.phtml');
	}

	public static function getAllowedOptions() {
		$translator = Zend_Registry::get('Zend_Translate');
		return array(
			array(
				'alias'   => $translator->translate('Member area sign-up box'),
				'option' => 'member:signup'
			),
			array(
				'alias'   => $translator->translate('Member area login box'),
				'option' => 'member:login'
			),
			array(
				'alias'   => $translator->translate('Member area logout button'),
				'option' => 'member:logout'
			)
		);
	}

	protected function _reInitDecorators($loginForm) {
	   	$loginForm->setDecorators(array(
   			'FormElements',
   			'Form'
   		));
		$loginForm->removeDecorator('HtmlTag');

		$loginForm->setElementDecorators(array(
			'ViewHelper',
			'Errors',
			'Label',
			array('HtmlTag', array('tag' => 'p'))
		));
		$loginForm->getElement('submit')->removeDecorator('Label');
		$loginForm->getElement('submit')->removeDecorator('HtmlTag');

		return $loginForm;
	}
}

