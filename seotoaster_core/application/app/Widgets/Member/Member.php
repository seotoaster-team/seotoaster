<?php

class Widgets_Member_Member extends Widgets_Abstract {

	private $_session;

	protected function  _init() {
		parent::_init();
		$this->_view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));
		$this->_website                 = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
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
		return $this->_view->render('login.phtml');
	}

	private function _renderMemberLogout() {
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_PAGE_PROTECTED)) {
			return '';
		}
		return '<a href="' . $this->_website->getUrl() . 'logout">Logout</a>';
	}

	private function _renderMemberDetails() {

	}
}

