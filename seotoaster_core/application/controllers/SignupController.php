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

            $configHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
            $userDefaultTimezone = $configHelper->getConfig('userDefaultTimezone');
            $userDefaultMobileCountryCode = $configHelper->getConfig('userDefaultPhoneMobileCode');

            if (!empty($formParams['mobilePhone'])) {
                $formParams['mobilePhone'] = Tools_System_Tools::cleanNumber($formParams['mobilePhone']);
            }

            if (!empty($formParams['desktopPhone'])) {
                $formParams['desktopPhone'] = Tools_System_Tools::cleanNumber($formParams['desktopPhone']);
            }

            if(!empty($formParams['email'])) {
                $this->_helper->session->signupEmailField = $formParams['email'];
            }

            if(!empty($formParams['fullName'])) {
                $this->_helper->session->signupFullNameField = $formParams['fullName'];
            }

            if(!empty($formParams['prefix'])) {
                $this->_helper->session->signupPrefixField = $formParams['prefix'];
            }

			if($signupForm->isValid($formParams)) {
				//save new user
				$user = new Application_Model_Models_User($signupForm->getValues());

                $timezone = $user->getTimezone();
                $mobileCountryCode = $user->getMobileCountryCode();
                $desktopCountryCode = $user->getDesktopCountryCode();
                if (empty($timezone) && !empty($userDefaultTimezone)) {
                    $user->setTimezone($userDefaultTimezone);
                }

                if (empty($mobileCountryCode) && !empty($userDefaultMobileCountryCode)) {
                    $mobileCountryCode = $userDefaultMobileCountryCode;
                    $user->setMobileCountryCode($mobileCountryCode);
                }

                if (empty($desktopCountryCode) && !empty($userDefaultMobileCountryCode)) {
                    $desktopCountryCode = $userDefaultMobileCountryCode;
                    $user->setDesktopCountryCode($desktopCountryCode);
                }

                if (!empty($mobileCountryCode) && !empty($formParams['mobilePhone'])) {
                    $mobileCountryPhoneCode = Zend_Locale::getTranslation($mobileCountryCode, 'phoneToTerritory');
                    $mobileCountryCodeValue = '+'.$mobileCountryPhoneCode;
                } else {
                    $mobileCountryCodeValue = null;
                }
                $user->setMobileCountryCodeValue($mobileCountryCodeValue);

                if (!empty($desktopCountryCode) && !empty($formParams['desktopPhone'])) {
                    $desktopCountryPhoneCode = Zend_Locale::getTranslation($desktopCountryCode, 'phoneToTerritory');
                    $desktopCountryCodeValue = '+'.$desktopCountryPhoneCode;
                } else {
                    $desktopCountryCodeValue = null;
                }
                $user->setDesktopCountryCodeValue($desktopCountryCodeValue);

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

