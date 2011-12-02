<?php

/**
 * UserController
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Backend_UserController extends Zend_Controller_Action {

	public function init() {
		parent::init();
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_USERS)) {
			$this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
		}
		$this->_helper->AjaxContext()->addActionContexts(array(
			'list'   => 'json',
			'delete' => 'json',
			'load'   => 'json',
            'export' => 'json'
		))->initContext('json');
		$this->view->websiteUrl = $this->_helper->website->getUrl();
	}

	public function manageAction() {
		$userForm = new Application_Form_User();
		if($this->getRequest()->isPost()) {
			if($userForm->isValid($this->getRequest()->getParams())) {
				$data       = $userForm->getValues();
				$user       = new Application_Model_Models_User($data);
				Application_Model_Mappers_UserMapper::getInstance()->save($user);
				$this->_helper->response->success('Added');
				exit;
			}
			else {
				$this->_helper->response->fail(Tools_Content_Tools::proccessFormMessagesIntoHtml($userForm->getMessages()));
				exit;
			}
		}
		$this->view->userForm = $userForm;
	}

	public function listAction() {
		$this->view->users     = Application_Model_Mappers_UserMapper::getInstance()->fetchAll();
		$this->view->usersList = $this->view->render('backend/user/list.phtml');
	}

	public function deleteAction() {
		if($this->getRequest()->isPost()) {
			$userId = $this->getRequest()->getParam('id');
			if(!$userId) {
				$this->_helper->response->fail('Can\'t remove user...');
				exit;
			}
			$userMapper = Application_Model_Mappers_UserMapper::getInstance();
			if($userMapper->delete($userMapper->find($userId))) {
				$this->_helper->response->success('Removed');
				exit;
			}
			$this->_helper->response->fail('Can\'t remove user...');
		}
	}

	public function loadAction() {
		if($this->getRequest()->isPost()) {
			$userId = $this->getRequest()->getParam('id');
			if(!$userId) {
				$this->_helper->response->fail('Cannot load user...');
				exit;
			}
			$user       = Application_Model_Mappers_UserMapper::getInstance()->find($userId);
			$result = array(
				'formId' => 'frm-user',
				'data'   => $user->toArray()
			);
			$this->_helper->response->success($result);
		}
	}

    public function exportAction() {
        if($this->getRequest()->isPost()) {
            if(Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_USERS)) {
                $users        = Application_Model_Mappers_UserMapper::getInstance()->fetchAll();
                $dataToExport = array();
                foreach($users as $user) {
                    $usrData = $user->toArray();
                    unset($usrData['password']);
                    unset($usrData['id']);
                    $dataToExport[] = $usrData;
                }
                Tools_System_Tools::arrayToCsv($dataToExport, array(
                    $this->_helper->language->translate('E-mail'),
                    $this->_helper->language->translate('Role'),
                    $this->_helper->language->translate('Full name'),
                    $this->_helper->language->translate('Last login date'),
                    $this->_helper->language->translate('Registration date'),
                    $this->_helper->language->translate('IP address')
                ));
                $this->_helper->response->success($this->_helper->language->translate('Users list exported'));
            }
            $this->_helper->response->fail($this->_helper->language->translate('Cannot export users list.'));
        }
    }
}

