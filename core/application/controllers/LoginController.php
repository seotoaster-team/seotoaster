<?php

class LoginController extends Zend_Controller_Action {

	private $_websiteData = array();

    public function init() {
		$this->_websiteData = Zend_Registry::get('website');
    }

    public function indexAction() {
        $loginForm = new Application_Form_Login();
		if($this->getRequest()->isPost()) {
			if($loginForm->isValid($this->getRequest()->getParams())) {
				$authAdapter = new Zend_Auth_Adapter_DbTable(
					Zend_Registry::get('dbAdapter'),
					'user',
					'email',
					'password',
					'MD5(?)'
				);
				$authAdapter->setIdentity($loginForm->getValue('email'));
				$authAdapter->setCredential($loginForm->getValue('password'));
				$authResult = $authAdapter->authenticate();
				if($authResult->isValid()) {
					$authUserData = $authAdapter->getResultRowObject(null, 'password');
					if(null !== $authUserData) {
						$user = new Application_Model_Models_User();
						$user->setId($authUserData->id);
						$user->setEmail($authUserData->email);
						$user->setRoleId($authUserData->role_id);
						$user->setFullName($authUserData->full_name);
						$user->setLasLogin($authUserData->last_login);
						$user->setRegDate($authUserData->reg_date);
						$this->_helper->session->setCurrentUser($user);
						unset($user);
						$this->_helper->cache->clean();
						$this->_redirect($this->_websiteData['url']);
					}
				}
				Zend_Debug::dump('Auth failed'); die();
			}
			else {
				Zend_Debug::dump('Not valid form'); die();
			}
		}
		else {
			$this->view->loginForm = $loginForm;
		}
	}

	public function logoutAction() {
		$this->_helper->getHelper('layout')->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->session->getSession()->unsetAll();
		$this->_helper->cache->clean();
		$this->_redirect($this->_websiteData['url']);
	}
}

