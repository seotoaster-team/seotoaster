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
			$this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
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

		if (!$isSuperAdminLogged) {
			$configForm->removeElement('suLogin');
			$configForm->removeElement('suPassword');
		} else {
			//initializing current superadmin user
			$userTable = new Application_Model_DbTable_User();
			$userMapper = Application_Model_Mappers_UserMapper::getInstance();
		}

		if ($this->getRequest()->isPost()){
			if ($configForm->isValid($this->getRequest()->getParams())){
				//proccessing language changing
				$selectedLang = $languageSelect->getValue();
				if ($selectedLang != $this->_helper->language->getCurrentLanguage()) {
					$this->_helper->language->setLanguage($selectedLang);
                    $languageSelect->setMultiOptions($this->_helper->language->getLanguages(false));
				}
				if ( $isSuperAdminLogged ) {
					$newPass	= $configForm->getElement('suPassword')->getValue();
					$newLogin	= $configForm->getElement('suLogin')->getValue();
					$adminDataModified = false;
					if (!empty($newPass) && md5($newPass) !== $loggedUser->getPassword() ){
						$loggedUser->setPassword($newPass);
						$adminDataModified = true;
					}

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


		if ($isSuperAdminLogged) {
			$configForm->getElement('suLogin')->setValue($loggedUser->getEmail());
			$configForm->getElement('suPassword')->setValue($loggedUser->getPassword());
		}

		$this->view->messages = $this->_helper->flashMessenger->getMessages();
		$this->view->configForm = $configForm;

		$triggers = Application_Model_Mappers_EmailTriggersMapper::getInstance()->getTriggers(true);
		$this->view->triggers = array_combine($triggers, $triggers);
		array_unshift($this->view->triggers,  'select trigger');
		$recipients = Application_Model_Mappers_EmailTriggersMapper::getInstance()->getReceivers(true);
		$this->view->recipients = array_combine($recipients, $recipients);
		array_unshift($this->view->recipients,  'select recipient');

		$templates = Application_Model_Mappers_TemplateMapper::getInstance()->findByType(Application_Model_Models_Template::TYPE_MAIL);
		$this->view->templates = array('select template');
		if (!empty($templates)){
			foreach ($templates as $tmpl) {
				$this->view->templates[$tmpl->getName()] = $tmpl->getName();
			}
		}

		$this->view->actions = Application_Model_Mappers_EmailTriggersMapper::getInstance()->fetchArray();
	}

    /**
     * Action for alter mail messasge before sending
     *
     * @return bool
     * @throws Exceptions_SeotoasterException
     */
    public function mailmessageAction() {
        $triggerName = $this->getRequest()->getParam('trigger', false);
        if(!$triggerName) {
            throw new Exceptions_SeotoasterException('Not enough parameter passed!');
        }
        $trigger = reset(Application_Model_Mappers_EmailTriggersMapper::getInstance()->findByTriggerName($triggerName)->toArray());
        if($this->getRequest()->isPost()) {
            $trigger = new Application_Model_Models_TriggerAction($trigger);
            $m = $this->getRequest()->getParam('msg');
            $trigger->setMessage($this->getRequest()->getParam('msg'));
            Application_Model_Mappers_EmailTriggersMapper::getInstance()->save($trigger);
            $this->_helper->response->success();
            return;
        }
        $this->_helper->response->success($trigger['message']);
        return true;
    }

    public function actionmailsAction() {
        if($this->getRequest()->isPost()) {
            $actions = $this->getRequest()->getParam('actions', false);
            if($actions !== false) {
                $removeActions =  array();
                foreach($actions as $action) {
                    if (isset($action['delete']) && $action['delete'] === "true"){
                        array_push($removeActions, $action['id']);
                        continue;
                    }

                    //add trigger automatically if not exists
                    //if(($exists = Application_Model_Mappers_EmailTriggersMapper::getInstance()->findByTriggerName($action['trigger'])->current()) === null) {
                    //    Application_Model_Mappers_EmailTriggersMapper::getInstance()->registerTrigger($action['trigger']);
                    // }

                    Application_Model_Mappers_EmailTriggersMapper::getInstance()->save($action);
                }
                if (!empty($removeActions)) {
                    Application_Model_Mappers_EmailTriggersMapper::getInstance()->delete($removeActions);
                }
                $this->_helper->response->success($this->_helper->language->translate('Changes saved'));
                return true;
            }
        }

        $pluginsTriggers = Tools_Plugins_Tools::fetchPluginsTriggers();
        $systemTriggers  = Tools_System_Tools::fetchSystemtriggers();
        $triggers        = is_array($pluginsTriggers) ? array_merge($systemTriggers, $pluginsTriggers) : $systemTriggers;

        $recipients                 = Application_Model_Mappers_EmailTriggersMapper::getInstance()->getReceivers(true);
        $this->view->recipients     = array_combine($recipients, $recipients);
        $this->view->mailTemplates  = Tools_Mail_Tools::getMailTemplatesHash();
        $this->view->triggers       = $triggers;
        $this->view->actionsOptions = array_merge(array('0' => $this->_helper->language->translate('Select event area')), array_combine(array_keys($triggers), array_map(function($trigger) {
            return ucfirst($trigger);
        }, array_keys($triggers))));
        $this->view->actions        = Application_Model_Mappers_EmailTriggersMapper::getInstance()->fetchArray();
    }
}