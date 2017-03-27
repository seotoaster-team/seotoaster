<?php

class Widgets_Member_Member extends Widgets_Abstract {

	protected $_session        = null;

    protected $_website        = null;

    protected $_flashMessanger = null;

    protected $_cacheable      = false;

    protected $_translator      = false;

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
        $signupForm = $this->_view->signupForm       = new Application_Form_Signup();
        $signupForm->addElement('text','PageId',array(
                'value' => $pageId,
                'class' => 'hidden',
                'id'    => 'PageId'));
        if(in_array(self::OPTION_NOCAPTCHA,$this->_options)) {
            $signupForm->removeElement('verification');
            $signupForm->addElement('text','token',array(
                    'value' => '',
                    'class' => 'hide',
                    'id'    => 'token'));
            $key = md5('signup'.$pageId);
            $this->_session->$key = $key;
        }
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

