<?php

class LoginController extends Zend_Controller_Action {

    public function init() {
		$this->view->websiteUrl = $this->_helper->website->getUrl();
    }

    public function indexAction() {
		$this->_helper->page->doCanonicalRedirect('go');
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
					$authUserData = $authAdapter->getResultRowObject(null, 'password');
					if(null !== $authUserData) {
						$user = new Application_Model_Models_User((array)$authUserData);
						$user->setLastLogin(date(Tools_System_Tools::DATE_MYSQL));
						$user->setIpaddress($_SERVER['REMOTE_ADDR']);
						$this->_helper->session->setCurrentUser($user);
						Application_Model_Mappers_UserMapper::getInstance()->save($user);
						unset($user);
						$this->_helper->cache->clean();
						if($authUserData->role_id == Tools_Security_Acl::ROLE_MEMBER) {
							$this->_memberRedirect();
						}
						if(isset($this->_helper->session->redirectUserTo)) {
							$this->_redirect($this->_helper->website->getUrl() . $this->_helper->session->redirectUserTo, array('exit' => true));
						}
						$this->_redirect((isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : $this->_helper->website->getUrl());
					}
				}

				$signInType = $this->getRequest()->getParam('singintype');
				if($signInType && $signInType == Tools_Security_Acl::ROLE_MEMBER) {
					$this->_memberRedirect(false);
				}

				$this->_checkRedirect(false, array('email' => $this->_helper->language->translate('There is no user with such login and password.')));
			}
			else {
				$this->_checkRedirect(false, array('email' => $this->_helper->language->translate('Login should be a valid email address')));
			}
		}
		else {
			//getting available system translations
            $this->view->languages = $this->_helper->language->getLanguages();
			//getting messages
            $errorMessages = $this->_helper->flashMessenger->getMessages();
            if (!empty($errorMessages)) {
                foreach ($errorMessages as $message) {
                    foreach ($message as $elementName => $msg) {
                        $loginForm->getElement($elementName)->setAttribs(array('class' => 'notvalid', 'title' => $msg));
                    }
                }
            }
			$this->view->messages   = $this->_helper->flashMessenger->getMessages();
			//unset url redirect set from any login widget
			unset($this->_helper->session->redirectUserTo);
            $loginForm->removeDecorator('HtmlTag');
            $loginForm->setElementDecorators(array(
                    'ViewHelper',
                    'Errors',
                    'Label',
                    array('HtmlTag', array('tag' => 'p'))
            ));
            $this->view->loginForm  = $loginForm;
		}
	}

	public function logoutAction() {
		$this->_helper->getHelper('layout')->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->session->getSession()->unsetAll();
		$this->_helper->cache->clean();
		$this->_checkRedirect($this->_helper->website->getUrl(), '');

	}

	private function _memberRedirect($success = true) {
		$landingPage = ($success) ? Tools_Page_Tools::getLandingPage(Application_Model_Models_Page::OPT_MEMLAND) : Tools_Page_Tools::getLandingPage(Application_Model_Models_Page::OPT_ERRLAND);
		if($landingPage instanceof Application_Model_Models_Page) {
			$this->redirect($this->_helper->website->getUrl() . $landingPage->getUrl(), array('exit' => true));
		}
	}

	private function _checkRedirect($url = '', $message = '') {
		if($message) {
			$this->_helper->flashMessenger->addMessage($message);
		}
		if(isset($_SERVER['HTTP_REFERER'])) {
			$this->_helper->redirector->gotoUrl($_SERVER['HTTP_REFERER'], array('exit' => true));
		}
		if(!$url) {
			$this->_helper->redirector->gotoRouteAndExit(array(
				'controller' => 'login',
				'action'     =>'index'
			));
		}
        $this->_helper->redirector->gotoUrlAndExit($url);
	}

	public function passwordretrieveAction() {
		$form = new Application_Form_PasswordRetrieve();
		if($this->getRequest()->isPost()) {
			if($form->isValid($this->getRequest()->getParams())) {
				$retrieveData = $form->getValues();
				$user = Application_Model_Mappers_UserMapper::getInstance()->findByEmail(filter_var($retrieveData['email'], FILTER_SANITIZE_EMAIL));
				//create new reset token and send e-mail to the user
				$resetToken = new Application_Model_Models_PasswordRecoveryToken(array(
					'saltString' => $retrieveData['email'],
					'expiredAt'  => date(Tools_System_Tools::DATE_MYSQL, strtotime('+1 day', time())),
					'userId'     => $user->getId()
				));
				$resetToken->registerObserver(new Tools_Mail_Watchdog(array(
                    'trigger' => Tools_Mail_SystemMailWatchdog::TRIGGER_PASSWORDRESET
				)));
				$resetTokenId = Application_Model_Mappers_PasswordRecoveryMapper::getInstance()->save($resetToken);
				if($resetTokenId) {
					$this->_helper->flashMessenger->setNamespace('passreset')->addMessage('We\'ve sent an email to '.$user->getEmail().' containing a temporary url that will allow you to reset your password for the next 24 hours. Please check your spam folder if the email doesn\'t appear within a few minutes.');
                    if(isset($this->_helper->session->retrieveRedirect)){
                        $redirectTo = $this->_helper->session->retrieveRedirect;
                        unset($this->_helper->session->retrieveRedirect);
                        $this->redirect($this->_helper->website->getUrl() . $redirectTo);
                    }
                    $this->_helper->redirector->gotoRoute(array(
						'controller' => 'login',
						'action'     => 'passwordretrieve'
					));
				}
			} else {
				$messages       = array_values($form->getMessages());
				$flashMessanger = $this->_helper->flashMessenger;
				foreach($messages as $messageData) {
					if(is_array($messageData)) {
						array_walk($messageData, function($msg) use($flashMessanger) {
							$flashMessanger->addMessage(array('email' => $msg));
						});
					} else {
						$flashMessanger->addMessage(array('email' => $messageData));
					}
				}
				if(isset($this->_helper->session->retrieveRedirect)){
                    $redirectTo = $this->_helper->session->retrieveRedirect;
                    unset($this->_helper->session->retrieveRedirect);
                    return $this->redirect($this->_helper->website->getUrl() . $redirectTo);
                }
                return $this->redirect($this->_helper->website->getUrl() . 'login/retrieve/');
			}
		}
        $errorMessages = $this->_helper->flashMessenger->getMessages();
        if (!empty($errorMessages)) {
            foreach ($errorMessages as $message) {
                foreach ($message as $elementName => $msg) {
                    $form->getElement($elementName)->setAttribs(array('class' => 'notvalid', 'title' => $msg));
                }
            }
        }
        $passResetMsg = $this->_helper->flashMessenger->getMessages('passreset');
        if (!empty($passResetMsg)) {
            $this->view->retrieveSuccessMessage = join($passResetMsg, PHP_EOL);
        }
        $form->removeDecorator('HtmlTag');
        $form->setElementDecorators(array(
                'ViewHelper',
                'Errors',
                'Label',
                array('HtmlTag', array('tag' => 'p'))
        ));
		$this->view->form     = $form;
	}

	public function passwordresetAction() {
		//check the get string for the tokens http://mytoaster.com/login/reset/email/myemail@mytoaster.com/token/adadajqwek123klajdlkasdlkq2e3
		$error = false;
		$form  = new Application_Form_PasswordReset();
		$email = filter_var($this->getRequest()->getParam('email', false), FILTER_SANITIZE_EMAIL);
		$token = filter_var($this->getRequest()->getParam('key', false), FILTER_SANITIZE_STRING);

		if(!$email || !$token) {
			$error = true;
		}
		$resetToken = Application_Model_Mappers_PasswordRecoveryMapper::getInstance()->findByTokenAndMail($token, $email);
		if(!$resetToken
			|| $resetToken->getStatus() != Application_Model_Models_PasswordRecoveryToken::STATUS_NEW
			|| $this->_isTokenExpired($resetToken)) {
				$error = true;
		}
		if($error) {
			$error = false;
			$this->_helper->flashMessenger->addMessage('Token is incorrect. Please, enter your e-mail one more time.');
			return $this->redirect($this->_helper->website->getUrl() . 'login/retrieve/');
		}

		if($this->getRequest()->isPost()) {
			if($form->isValid($this->getRequest()->getParams())) {
				$resetToken->registerObserver(new Tools_Mail_Watchdog(array(
                    'trigger' => Tools_Mail_SystemMailWatchdog::TRIGGER_PASSWORDCHANGE
				)));
				$resetData = $form->getValues();
				$mapper    = Application_Model_Mappers_UserMapper::getInstance();
				$user      = $mapper->find($resetToken->getUserId());
				$user->setPassword($resetData['password']);
				$mapper->save($user);
				$resetToken->setStatus(Application_Model_Models_PasswordRecoveryToken::STATUS_USED);
				Application_Model_Mappers_PasswordRecoveryMapper::getInstance()->save($resetToken);
				$this->_helper->flashMessenger->addMessage($this->_helper->language->translate('Your password was reset.'));
                $roleId = $user->getRoleId();
                if($roleId != Tools_Security_Acl::ROLE_ADMIN && $roleId != Tools_Security_Acl::ROLE_SUPERADMIN){
                    return $this->redirect($this->_helper->website->getUrl());
                }
				return $this->redirect($this->_helper->website->getUrl() . 'go');
			} else {
				$this->_helper->flashMessenger->addMessage($this->_helper->language->translate('Passwords should match'));
				return $this->redirect($resetToken->getResetUrl());
			}
		}
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
		$this->view->form = $form;
	}

	/**
	 * Check if the token is expired. If so change status and return true.
	 *
	 * @param Application_Model_Models_PasswordRecoveryToken $token
	 * @return bool
	 */
	private function _isTokenExpired(Application_Model_Models_PasswordRecoveryToken $token) {
		if(strtotime($token->getExpiredAt()) < time()) {
			$token->setStatus(Application_Model_Models_PasswordRecoveryToken::STATUS_EXPIRED);
			Application_Model_Mappers_PasswordRecoveryMapper::getInstance()->save($token);
			return true;
		}
		return false;
	}
}

