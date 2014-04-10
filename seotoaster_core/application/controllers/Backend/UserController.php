<?php

/**
 * UserController
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Backend_UserController extends Zend_Controller_Action {

    /**
     * @var Helpers_Action_Session
     */
    private $_websiteHelper = null;
    /**
     * @var Zend_Db_Table
     */
    private $_zendDbTable;

    private $_websiteUrl;

	public function init() {
		parent::init();
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_USERS)) {
			$this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
		}
		$this->_helper->AjaxContext()->addActionContexts(array(
			'list'   => 'json',
			'delete' => 'json',
			'load'   => 'json'
		))->initContext('json');
		$this->view->websiteUrl = $this->_helper->website->getUrl();
        $this->_websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $this->_websiteUrl = $this->_websiteHelper->getUrl();
        $this->_zendDbTable = new Zend_Db_Table();
	}

	public function manageAction() {
		$userForm = new Application_Form_User();
        $userForm->getElement('password')->setRequired(false);
		if($this->getRequest()->isPost()) {
            //if we are updating
            $userId = $this->getRequest()->getParam('id');
            if($userId) {
                $userForm->setId($userId);
            }
			if($userForm->isValid($this->getRequest()->getParams())) {
				$data       = $userForm->getValues();
				$user       = new Application_Model_Models_User($data);
				Application_Model_Mappers_UserMapper::getInstance()->save($user);
				$this->_helper->response->success($this->_helper->language->translate('Saved'));
				exit;
			}
			else {
                $this->_helper->response->fail(Tools_Content_Tools::proccessFormMessages($userForm->getMessages()));
				exit;
			}
		}

        $pnum = (int)filter_var($this->getParam('pnum'), FILTER_SANITIZE_NUMBER_INT);
        $offset = 0;
        if ($pnum) {
            $offset = 10 * ($pnum - 1);
        }

        $select = $this->_zendDbTable->getAdapter()->select()->from('user');

        $by = filter_var($this->getParam('by', 'last_login'), FILTER_SANITIZE_STRING);
        $order = filter_var($this->getParam('order', 'DESC'), FILTER_SANITIZE_STRING);
        if (!in_array($order, array('ASC', 'DESC'))) {
            $order = 'DESC';
        }

        $select = $select->order($by . ' ' . $order);
        $adapter = new Zend_Paginator_Adapter_DbSelect($select);
        $users = $adapter->getItems($offset, 10);
        $userPaginator = new Zend_Paginator($adapter);
        $userPaginator->setCurrentPageNumber($pnum);
        $userPaginator->setItemCountPerPage(10);

        $pager = $this->view->paginationControl($userPaginator, 'Sliding', 'backend/user/pager.phtml',
            array(
                'urlData' => $this->_websiteUrl . 'backend/backend_user/manage',
                'order'   => '/by/' . $by . '/order/' . $order
            )
        );

        if ($order === 'DESC') {
            $order = 'ASC';
        } else {
            $order = 'DESC';
        }
        $this->view->order = $order;
        $this->view->pager = $pager;
        $this->view->users = $users;
        $this->view->helpSection = 'users';
        $this->view->userForm = $userForm;
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
                    unset($usrData['attributes']);
                    $dataToExport[] = $usrData;
                }
                $exportResult = Tools_System_Tools::arrayToCsv($dataToExport, array(
                    $this->_helper->language->translate('E-mail'),
                    $this->_helper->language->translate('Role'),
                    $this->_helper->language->translate('Full name'),
                    $this->_helper->language->translate('Last login date'),
                    $this->_helper->language->translate('Registration date'),
                    $this->_helper->language->translate('IP address')
                ));
				if($exportResult) {
					$usersArchive = Tools_System_Tools::zip($exportResult);
					$this->getResponse()->setHeader('Content-Disposition', 'attachment; filename=' . Tools_Filesystem_Tools::basename($usersArchive))
						->setHeader('Content-type', 'application/force-download');
					readfile($usersArchive);
					$this->getResponse()->sendResponse();
				}
				exit;
            }
        }
    }
}

