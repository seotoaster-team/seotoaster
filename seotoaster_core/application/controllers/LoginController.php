<?php

class LoginController extends Zend_Controller_Action {

    public function init() {
		$this->view->websiteUrl = $this->_helper->website->getUrl();
    }

    public function indexAction() {

		//if logged in user trys to go to the login page - redirect him to the main page

		if(Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_PAGE_PROTECTED)) {
			$this->_redirect($this->_helper->website->getUrl());
		}

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
					$authUserData = $authAdapter->getResultRowObject();
					if(null !== $authUserData) {
						$user = new Application_Model_Models_User();
						$user->setId($authUserData->id);
						$user->setEmail($authUserData->email);
						$user->setPassword($authUserData->password);
						$user->setRoleId($authUserData->role_id);
						$user->setFullName($authUserData->full_name);
						$user->setLastLogin(date('Y-m-d H:i:s', time()));
						$user->setRegDate($authUserData->reg_date);
						$this->_helper->session->setCurrentUser($user);

						$userMapper = new Application_Model_Mappers_UserMapper();
						$userMapper->save($user);

						unset($user);
						$this->_helper->cache->clean();
						$this->_redirect($this->_helper->website->getUrl());
					}
				}
				$this->_helper->flashMessenger->addMessage('There is no user with such login and password.');
				$this->_helper->redirector->gotoRoute(array('controller'=>'login', 'action'=>'index'));
			}
			else {
				$this->_helper->flashMessenger->addMessage('Login should be a valid email address');
				$this->_helper->redirector->gotoRoute(array('controller'=>'login', 'action'=>'index'));
			}
		}
		else {
			//getting flags
			$this->view->flagsFiles = Tools_Filesystem_Tools::findFilesByExtension($this->_helper->website->getPath() . 'system/images/flags', 'png', false, true);

			//getting messages
			$this->view->messages = $this->_helper->flashMessenger->getMessages();

			$this->view->loginForm  = $loginForm;
		}
	}

	public function logoutAction() {
		$this->_helper->getHelper('layout')->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->session->getSession()->unsetAll();
		$this->_helper->cache->clean();
		$this->_redirect($this->_helper->website->getUrl());
	}
}

