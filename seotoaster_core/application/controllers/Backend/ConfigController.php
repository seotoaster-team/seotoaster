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
		$this->view->websiteUrl = $this->_helper->website->getUrl();

		$this->_websiteConfig	= Zend_Registry::get('website');

		$this->_translator = Zend_Registry::get('Zend_Translate');

		$this->_configMapper = Application_Model_Mappers_ConfigMapper::getInstance();
	}

	public function configAction() {
		$configForm = new Application_Form_Config();
		$configForm->setAction($this->_helper->url->url());

		$langList = $this->_helper->language->getLanguages(false);
		$languageSelect = $configForm->getElement('language');
		$languageSelect->setMultiOptions($langList);
		$languageSelect->setValue($this->_helper->language->getCurrentLanguage());
		
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
				}
				if ( $isSuperAdminLogged ) {
					$newPass	= $configForm->getElement('suPassword')->getValue();
					$newLogin	= $configForm->getElement('suLogin')->getValue();
					$adminDataModified = false;
					if (!empty($newPass) && md5($newPass) !== $loggedUser->getPassword() ){
						$loggedUser->setPassword( md5($newPass) );
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
						$userMapper->save($loggedUser);
					}
				}

				//proccessing form to db
				$this->_configMapper->save($configForm->getValues());
				$this->_helper->flashMessenger->addMessage('Setting saved');
			} else {
				if ($configForm->proccessErrors()) {
					$this->_helper->flashMessenger->addMessage('Some fields are wrong');
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
	}


}