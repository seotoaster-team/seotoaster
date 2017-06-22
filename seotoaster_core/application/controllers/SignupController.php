<?php

/**
 * SignupController
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class SignupController extends Zend_Controller_Action {

	public function init() {
		$this->view->websiteUrl = $this->_helper->website->getUrl();
    }

	public function indexAction() {

	}

	public function signupAction() {
		$this->_helper->viewRenderer->setNoRender(true);
		if($this->getRequest()->isPost()) {
			$signupForm = new Application_Form_Signup();
            $formData = $this->_request->getParams();
            if (empty($formData['PageId'])) {
                $this->_helper->flashMessenger->addMessage('missing page id');
                $signupPageUrl = $this->_helper->session->signupPageUrl;
                $this->redirect($this->_helper->website->getUrl() . ($signupPageUrl ? $signupPageUrl : ''));
            }

            $pageId = $formData['PageId'];
            $signupFormKeyParams = 'signUpKeyParams'.$pageId;
            if (!isset($this->_helper->session->$signupFormKeyParams)) {
                $this->_helper->flashMessenger->addMessage('missing signup key');
                $signupPageUrl = $this->_helper->session->signupPageUrl;
                $this->redirect($this->_helper->website->getUrl() . ($signupPageUrl ? $signupPageUrl : ''));
            }

            $options = $this->_helper->session->$signupFormKeyParams;
            if (empty($options)) {
                foreach (Widgets_Member_Member::$_oldCompatibilityFields as $field) {
                    $signupForm->removeElement($field);
                }
            }

            $key = md5('signup'.$pageId);
            if(isset($this->_helper->session->$key)) {
                if(isset($formData['token']) && $formData['token'] === ''){
                    $signupForm->removeElement('verification');
                }
                unset($this->_helper->session->$key);
            }

            $signupForm = Tools_System_Tools::adjustFormFields($signupForm, $options, Widgets_Member_Member::$_formMandatoryFields);
            $formParams = $this->getRequest()->getParams();

            if (!empty($formParams['mobilePhone'])) {
                $formParams['mobilePhone'] = Tools_System_Tools::cleanNumber($formParams['mobilePhone']);
            }

            if (!empty($formParams['desktopPhone'])) {
                $formParams['desktopPhone'] = Tools_System_Tools::cleanNumber($formParams['desktopPhone']);
            }

            if (!empty($formParams['mobileCountryCode'])) {
                $mobileCountryPhoneCode = Zend_Locale::getTranslation($formParams['mobileCountryCode'], 'phoneToTerritory');
                $formParams['mobileCountryCodeValue'] = '+'.$mobileCountryPhoneCode;
            } else {
                $formParams['mobileCountryCodeValue'] = null;
            }
            if (!empty($formParams['desktopCountryCode'])) {
                $mobileCountryPhoneCode = Zend_Locale::getTranslation($formParams['desktopCountryCode'], 'phoneToTerritory');
                $formParams['desktopCountryCodeValue'] = '+'.$mobileCountryPhoneCode;
            } else {
                $formParams['desktopCountryCodeValue'] = null;
            }

			if($signupForm->isValid($formParams)) {
				//save new user
				$user = new Application_Model_Models_User($signupForm->getValues());

				$user->registerObserver(new Tools_Mail_Watchdog(array(
					'trigger' => Tools_Mail_SystemMailWatchdog::TRIGGER_SIGNUP
				)));

				$user->setRoleId(Tools_Security_Acl::ROLE_MEMBER);
				if (isset($this->_helper->session->refererUrl)){
					$user->setReferer($this->_helper->session->refererUrl);
				}
				$signupResult = Application_Model_Mappers_UserMapper::getInstance()->save($user);
				if(!$user->getId()) {
					$user->setId($signupResult);
				}

				//send mails by notifying mail observer about successful sign-up,
				$user->notifyObservers();


				//redirect to signup landing page
				$signupLandingPage = Tools_Page_Tools::getLandingPage(Application_Model_Models_Page::OPT_SIGNUPLAND);
				if($signupLandingPage instanceof Application_Model_Models_Page) {
					$this->_redirect($this->_helper->website->getUrl() . $signupLandingPage->getUrl());
					exit;
				} else {
					$this->_redirect($this->_helper->website->getUrl());
				}
			}
			else {
				$this->_helper->flashMessenger->addMessage(Tools_Content_Tools::proccessFormMessagesIntoHtml($signupForm->getMessages(), get_class($signupForm)));
				$signupPageUrl = $this->_helper->session->signupPageUrl;
				unset($this->_helper->session->signupPageUrl);
				$this->_redirect($this->_helper->website->getUrl() . ($signupPageUrl ? $signupPageUrl : ''));
			}
		}
	}

}

