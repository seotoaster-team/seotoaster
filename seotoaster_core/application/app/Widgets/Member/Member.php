<?php

class Widgets_Member_Member extends Widgets_Abstract {

	private $_session;

	protected function  _init() {
		parent::_init();
		$this->_view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));
		$this->_website          = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$this->_view->websiteUrl = $this->_website->getUrl();
		$this->_session          = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
	}

	protected function  _load() {
		$option       = array_shift($this->_options);
		$rendererName = '_renderMember' . ucfirst($option);
		if(method_exists($this, $rendererName)) {
			return $this->$rendererName($this->_options);
		}
		throw new Exceptions_SeotoasterWidgetException($this->_translator->translate('Wrong member type'));
	}

	private function _renderMemberLogin() {
		$this->_view->userRole  = $this->_session->getCurrentUser()->getRoleId();
		$this->_view->loginForm = new Application_Form_Login();
		$this->_view->messages  = (isset($this->_session->errMemeberLogin)) ? $this->_session->errMemeberLogin : array();
		unset($this->_session->errMemeberLogin);
		if(isset($this->_options[0])) {
			$this->_session->redirectUserTo = $this->_options[0];
		}
		return $this->_view->render('login.phtml');
	}

	private function _renderMemberLogout() {
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_PAGE_PROTECTED)) {
			return '';
		}
		return '<a href="' . $this->_website->getUrl() . 'logout" class="logout">Logout</a>';
	}

	private function _renderMemberSignup() {
		$this->_view->signupForm       = new Application_Form_Signup();
		$flashMessanger                = Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger');
		$errorMessages                 = $flashMessanger->getMessages();
		$this->_session->signupPageUrl = $this->_toasterOptions['url'];
		$this->_view->errors           = ($errorMessages) ? $errorMessages : null;
		return $this->_view->render('signup.phtml');
	}

	private function _renderMemberDetails() {

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
}

