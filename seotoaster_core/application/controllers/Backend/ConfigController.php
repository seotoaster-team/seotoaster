<?php

/**
 * Backend_ConfigController
 *
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Backend_ConfigController extends Zend_Controller_Action {

	public function  init() {
		parent::init();
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_CONFIG)) {
			$this->redirect($this->_helper->website->getUrl(), array('exit' => true));
		}
		$this->view->websiteUrl  = $this->_helper->website->getUrl();
		$this->_websiteConfig	 = Zend_Registry::get('website');
        $this->view->helpSection = 'config';

		$this->_translator = Zend_Registry::get('Zend_Translate');

		$this->_configMapper = Application_Model_Mappers_ConfigMapper::getInstance();
	}

	public function configAction() {
		$configForm = new Application_Form_Config();
		$configForm->setAction($this->_helper->url->url());

		$languageSelect = $configForm->getElement('language');
		$languageSelect->setMultiOptions($this->_helper->language->getLanguages(false));

		$loggedUser = $this->_helper->session->getCurrentUser();

		$isSuperAdminLogged = ($loggedUser->getRoleId() === Tools_Security_Acl::ROLE_SUPERADMIN);
		$this->view->isSuperAdmin = $isSuperAdminLogged;

		if ($this->getRequest()->isPost()) {
            if (!$isSuperAdminLogged) {
                $configForm->removeElement('suLogin');
                $configForm->removeElement('suPassword');
                $configForm->removeElement('canonicalScheme');
                $configForm->removeElement('recapthaPublicKey');
                $configForm->removeElement('recapthaPrivateKey');
            }
            else {
                //initializing current superadmin user
                $userTable  = new Application_Model_DbTable_User();
                $userMapper = Application_Model_Mappers_UserMapper::getInstance();
            }

			$configForm = Tools_System_Tools::addTokenValidatorZendForm($configForm, Tools_System_Tools::ACTION_PREFIX_CONFIG);

			if ($configForm->isValid($this->getRequest()->getParams())) {
				//proccessing language changing
				$selectedLang = $languageSelect->getValue();
				if ($selectedLang != $this->_helper->language->getCurrentLanguage()) {
					$this->_helper->language->setLanguage($selectedLang);
                    $languageSelect->setMultiOptions($this->_helper->language->getLanguages(false));
				}
				if ($isSuperAdminLogged) {
                    // Update modified templates in developer mode and clean concatcss cache
                    if (!((bool) $configForm->getElement('enableDeveloperMode')->getValue())
                        && (bool) $this->_helper->config->getConfig('enableDeveloperMode')
                    ) {
                        try {
                            Tools_Theme_Tools::applyTemplates($this->_helper->config->getConfig('currentTheme'), true);
                            $this->_helper->cache->clean(false, false, array('concatcss'));
                        }
                        catch (Exception $e) {
                            $e->getMessage();
                        }
                    }

					$newPass	= $configForm->getElement('suPassword')->getValue();
					$newLogin	= $configForm->getElement('suLogin')->getValue();
					$adminDataModified = false;
                    // checking if there is new su password
					if (!empty($newPass) && md5($newPass) !== $loggedUser->getPassword() ){
						$loggedUser->setPassword($newPass);
						$adminDataModified = true;
					}
                    // checking if su email has been changed
					if ($newLogin != $loggedUser->getEmail()) {
						$usersWithSuchEmail = $userTable->fetchAll( $userTable->getAdapter()->quoteInto('email = ?', $newLogin) );
						if (! $usersWithSuchEmail->count() ) {
							$loggedUser->setEmail($newLogin);
							$adminDataModified = true;
						}
					}
					if ($adminDataModified === true) {
						if (!$userMapper->save($loggedUser)){
							unset($newLogin);
						}
					}
				}

				//$showMemberOnlyPages = intval($configForm->getElement('memPagesInMenu')->getValue());

				//proccessing form to db
				$config = $configForm->getValues();
				if (isset($newLogin)){
					$config['adminEmail'] = $newLogin;
				}
				if ($config['smtpPassword'] === null && null === $this->getRequest()->getParam('smtpPassword', null)){
					unset($config['smtpPassword']);
				}

				if ($config['inlineEditor'] !== $this->_helper->config->getConfig('inlineEditor')){
					$this->_helper->cache->clean(false, false, array('Widgets_AbstractContent'));
				}
				$this->_configMapper->save($config);
				$this->_helper->flashMessenger->addMessage('Setting saved');
			} else {
				if ($configForm->proccessErrors()) {
					$this->_helper->flashMessenger->addMessage('Some fields are wrong');
				}
			}

			if (false !== ($actions = $this->_request->getParam('actions', false))){
				$removeActions =  array();
				foreach($actions as $action) {
					if (isset($action['delete']) && $action['delete'] === "true"){
						array_push($removeActions, $action['id']);
						continue;
					}
					Application_Model_Mappers_EmailTriggersMapper::getInstance()->save($action);
				}
				if (!empty($removeActions)) {
					Application_Model_Mappers_EmailTriggersMapper::getInstance()->delete($removeActions);
				}
			}
		} else {
			// loading config from db
			$currentConfig = $this->_configMapper->getConfig();

			if (!isset ($currentConfig['language'])){
				$currentConfig['language'] = $this->_helper->language->getCurrentLanguage();
			}

			if (is_array($currentConfig) && !empty ($currentConfig)){
				$configForm->setOptions($currentConfig);
			}
		}

		$secureToken = Tools_System_Tools::initZendFormCsrfToken($configForm, Tools_System_Tools::ACTION_PREFIX_CONFIG);

        $this->view->secureToken = $secureToken;
        
		if ($isSuperAdminLogged) {
			$suadmin = Application_Model_Mappers_UserMapper::getInstance()->findByRole(Tools_Security_Acl::ROLE_SUPERADMIN);
            $suadminEmail = $suadmin->getEmail();
            $suPassword = $suadmin->getPassword();
            $configForm->getElement('suLogin')->setValue($suadminEmail);
			$configForm->getElement('suPassword')->setValue($suPassword);
		}

		$this->view->messages = $this->_helper->flashMessenger->getMessages();
		$this->view->configForm = $configForm;
	}

    /**
     * Action for alter mail message before sending
     *
     * @return bool
     * @throws Exceptions_SeotoasterException
     */
    public function mailmessageAction() {
        $triggerName = $this->getRequest()->getParam('trigger', false);
        if(!$triggerName) {
            throw new Exceptions_SeotoasterException('Not enough parameter passed!');
        }
        $trigger = Application_Model_Mappers_EmailTriggersMapper::getInstance()->findByTriggerName($triggerName)->toArray();
        $trigger = reset($trigger);
        $this->_helper->response->success($trigger['message']);
        return true;
    }

    public function actionmailsAction() {
        if($this->getRequest()->isPost()) {
            $actions = $this->getRequest()->getParam('actions', false);
            $secureToken = $this->getRequest()->getParam('secureToken', false);
			$tokenValid = Tools_System_Tools::validateToken($secureToken, Tools_System_Tools::ACTION_PREFIX_ACTIONEMAILS);
			if (!$tokenValid) {
				$this->_helper->response->fail('');
			}
            if($actions !== false) {
                $removeActions =  array();
                $emailTriggerMapper = Application_Model_Mappers_EmailTriggersMapper::getInstance();
                foreach($actions as $action) {
                    if (isset($action['delete']) && $action['delete'] === "true"){
                        array_push($removeActions, $action['id']);
                        continue;
                    }
                    $emailTriggerMapper->save($action);
                }
                if (!empty($removeActions)) {
                    $emailTriggerMapper->delete($removeActions);
                }
                $this->_helper->response->success($this->_helper->language->translate('Changes saved'));
                return true;
            }
        }

        $secureToken = Tools_System_Tools::initSecureToken(Tools_System_Tools::ACTION_PREFIX_ACTIONEMAILS);

        $pluginsTriggers = Tools_Plugins_Tools::fetchPluginsTriggers();
        $systemTriggers  = Tools_System_Tools::fetchSystemtriggers();
        $triggers        = is_array($pluginsTriggers) ? array_merge($systemTriggers, $pluginsTriggers) : $systemTriggers;
        $services        = array('email' => 'e-mail', 'sms' => 'sms');
        $recipients                 = Application_Model_Mappers_EmailTriggersMapper::getInstance()->getReceivers(true);
        $this->view->recipients     = array_combine($recipients, $recipients);
        $this->view->mailTemplates  = Tools_Mail_Tools::getMailTemplatesHash();
        $this->view->triggers       = $triggers;
        $this->view->services       = $services;
        $this->view->secureToken = $secureToken;
        $this->view->actionsOptions = array_merge(array('0' => $this->_helper->language->translate('Select event area')), array_combine(array_keys($triggers), array_map(function($trigger) {
            return str_replace('-', ' ', ucfirst($trigger));
        }, array_keys($triggers))));
        $this->view->actions        = Application_Model_Mappers_EmailTriggersMapper::getInstance()->fetchArray();
    }
}