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

    private $_session;

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
        $this->_session = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
	}

	public function manageAction() {
		$userForm = new Application_Form_User();
        $userForm->getElement('password')->setRequired(false);
        $listMasksMapper = Application_Model_Mappers_MasksListMapper::getInstance();
		if($this->getRequest()->isPost()) {
            //if we are updating
            $userId = $this->getRequest()->getParam('id');
            if($userId) {
                $userForm->setId($userId);
            }

            $userForm = Tools_System_Tools::addTokenValidatorZendForm($userForm, Tools_System_Tools::ACTION_PREFIX_USERS);

            if($userForm->isValid($this->getRequest()->getParams())) {
				$data       = $userForm->getValues();
                $this->_processUser($data, $userId);

                $this->_helper->response->success($this->_helper->language->translate('Saved'));
				exit;
			}
			else {
                $this->_helper->response->fail(Tools_Content_Tools::proccessFormMessages($userForm->getMessages()));
				exit;
			}
		}

        $secureToken = Tools_System_Tools::initZendFormCsrfToken($userForm, Tools_System_Tools::ACTION_PREFIX_USERS);
        $this->view->secureToken = $secureToken;

        $pnum = (int)filter_var($this->getParam('pnum'), FILTER_SANITIZE_NUMBER_INT);
        $offset = 0;
        if ($pnum) {
            $offset = 10 * ($pnum - 1);
        }

        $select = $this->_zendDbTable->getAdapter()->select()->from('user');

        $by = filter_var($this->getParam('by', 'last_login'), FILTER_SANITIZE_STRING);
        $order = filter_var($this->getParam('order', 'desc'), FILTER_SANITIZE_STRING);
        $searchKey = filter_var($this->getParam('key'), FILTER_SANITIZE_STRING);

        if (!in_array($order, array('asc', 'desc'))) {
            $order = 'desc';
        }

        $select = $select->order($by . ' ' . $order);

        $paginatorOrderLink = '/by/' . $by . '/order/' . $order;
        if (!empty($searchKey)) {
            $select->where('email LIKE ?', '%'.$searchKey.'%')
                ->orWhere('full_name LIKE ?', '%'.$searchKey.'%')
                ->orWhere('role_id LIKE ?', '%'.$searchKey.'%')
                ->orWhere('last_login LIKE ?', '%'. date("Y-m-d", strtotime($searchKey)).'%')
                ->orWhere('ipaddress LIKE ?', '%'.$searchKey.'%');
            $paginatorOrderLink .= '/key/' . $searchKey;
        }

        $adapter = new Zend_Paginator_Adapter_DbSelect($select);
        $users = $adapter->getItems($offset, 10);
        $userPaginator = new Zend_Paginator($adapter);
        $userPaginator->setCurrentPageNumber($pnum);
        $userPaginator->setItemCountPerPage(10);

        $pager = $this->view->paginationControl($userPaginator, 'Sliding', 'backend/user/pager.phtml',
            array(
                'urlData' => $this->_websiteUrl . 'backend/backend_user/manage',
                'order'   => $paginatorOrderLink
            )
        );

        if ($order === 'desc') {
            $order = 'asc';
        } else {
            $order = 'desc';
        }

        if (!empty($searchKey)){
            $this->view->orderParam = $order . '/key/' . $searchKey;
        } else {
            $this->view->orderParam = $order;
        }

        $oldMobileFormat = $this->_helper->config->getConfig('oldMobileFormat');
        if (!empty($oldMobileFormat)) {
            $oldMobileFormat = true;
        }

        $this->view->by = $by;
        $this->view->order = $order;
        $this->view->key = $searchKey;
        $this->view->pager = $pager;
        $this->view->users = $users;
        $this->view->helpSection = 'users';
        $this->view->userForm = $userForm;
        $this->view->mobileMasks = $listMasksMapper->getListOfMasksByType(Application_Model_Models_MaskList::MASK_TYPE_MOBILE);
        $this->view->desktopMasks = $listMasksMapper->getListOfMasksByType(Application_Model_Models_MaskList::MASK_TYPE_DESKTOP);
        $this->view->oldMobileFormat = $oldMobileFormat;
	}

	public function deleteAction() {
		if($this->getRequest()->isDelete()) {
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
            if ($user instanceof Application_Model_Models_User) {
                $userData = $user->toArray();
                if (empty($userData['timezone'])) {
                    $userData['timezone'] = '0';
                }
                if (empty($userData['desktopCountryCode'])) {
                    $userData['desktopCountryCode'] = 'US';
                }
                if (empty($userData['mobileCountryCode'])) {
                    $userData['mobileCountryCode'] = 'US';
                }

                $result = array(
                    'formId' => 'frm-user',
                    'data' => $userData
                );
                $this->_helper->response->success($result);
            }
            $this->_helper->response->fail($this->_helper->language->translate('User doesn\'t exists'));
		}
	}

    public function sendinvitationAction()
    {
        if ($this->getRequest()->isPost() && Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_USERS)) {
            $userForm = new Application_Form_User();
            $userForm->getElement('password')->setRequired(false);
            $userInvitationEmail = filter_var($this->getRequest()->getParam('email'), FILTER_SANITIZE_STRING);
            $userFullName = filter_var($this->getRequest()->getParam('fullName'), FILTER_SANITIZE_STRING);
            $emailValidator = new Zend_Validate_EmailAddress();

            if (!$emailValidator->isValid($userInvitationEmail)) {
                $this->_helper->response->fail($this->_helper->language->translate('Not valid email address'));
            }

            if (empty($userFullName)) {
                $this->_helper->response->fail($this->_helper->language->translate('Please provide user name'));
            }

            $userId = $this->getRequest()->getParam('id');
            if (!empty($userId) && is_numeric($userId)) {
                $userForm->setId($userId);
            }

            $userForm = Tools_System_Tools::addTokenValidatorZendForm($userForm, Tools_System_Tools::ACTION_PREFIX_USERS);

            if ($userForm->isValid($this->getRequest()->getParams())) {
                $data       = $userForm->getValues();
                $userModel = $this->_processUser($data, $userId);
                $email = $userModel->getEmail();
                $userModel = Application_Model_Mappers_UserMapper::getInstance()->findByEmail($email);
                if ($userModel instanceof Application_Model_Models_User) {
                    $userModel->removeAllObservers();
                    $resetToken = Tools_System_Tools::saveResetToken($email, $userModel->getId());
                    if ($resetToken instanceof Application_Model_Models_PasswordRecoveryToken) {
                        $userModel->registerObserver(new Tools_Mail_Watchdog(array(
                            'trigger' => Tools_Mail_SystemMailWatchdog::TRIGGER_USERINVITATION,
                            'resetToken' => $resetToken
                        )));

                        $userModel->notifyObservers();

                        $this->_helper->response->success($this->_helper->language->translate('Invitation email has been sent'));
                    } else {
                        $this->_helper->response->fail($this->_helper->language->translate('Can\'t generate reset token'));
                    }
                }
                $this->_helper->response->fail($this->_helper->language->translate('User doesn\'t exist'));
            }
            else {
                $this->_helper->response->fail(Tools_Content_Tools::proccessFormMessages($userForm->getMessages()));
            }

        }
    }

    /**
     * Process user data
     *
     * @param array $data
     * @param int $currentUserId current user id
     * @return Application_Model_Models_User
     * @throws Exceptions_SeotoasterException
     */
    private function _processUser($data, $currentUserId)
    {
        $data['mobilePhone'] = preg_replace('~[^\d]~ui', '', $data['mobilePhone']);
        $data['desktopPhone'] = preg_replace('~[^\d]~ui', '', $data['desktopPhone']);
        if (!empty($data['mobileCountryCode'])) {
            $mobileCountryPhoneCode = Zend_Locale::getTranslation($data['mobileCountryCode'], 'phoneToTerritory');
            $data['mobile_country_code_value'] = '+'.$mobileCountryPhoneCode;
        } else {
            $data['mobile_country_code_value'] = null;
        }
        if (!empty($data['desktopCountryCode'])) {
            $mobileCountryPhoneCode = Zend_Locale::getTranslation($data['desktopCountryCode'], 'phoneToTerritory');
            $data['desktop_country_code_value'] = '+'.$mobileCountryPhoneCode;
        } else {
            $data['desktop_country_code_value'] = null;
        }
        $user       = new Application_Model_Models_User($data);
        $uId = Application_Model_Mappers_UserMapper::getInstance()->save($user);
        $attrNamesArr = filter_var_array($this->getRequest()->getParam('attrName', array()), FILTER_SANITIZE_STRING);
        $attrValuesArr = filter_var_array($this->getRequest()->getParam('attrValue', array()), FILTER_SANITIZE_STRING);
        if ($attrNamesArr) {
            foreach ($attrNamesArr as $key => $value) {
                if(empty($value) || empty($attrValuesArr[$key])) {
                    continue;
                }
                $user->setAttribute($value, $attrValuesArr[$key]);
            }
            if (empty($currentUserId)) {
                $user->setId((int)$uId);
            }
            Application_Model_Mappers_UserMapper::saveUserAttributes($user);
        }

        return $user;
    }

    public function exportAction() {
        if($this->getRequest()->isPost() && Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_USERS)) {
            $users        = Application_Model_Mappers_UserMapper::getInstance()->getUserList();
            if(!empty($users)){
                $exportResult = Tools_System_Tools::arrayToCsv($users, array(
                    $this->_helper->language->translate('E-mail'),
                    $this->_helper->language->translate('Role'),
                    $this->_helper->language->translate('Full name'),
                    $this->_helper->language->translate('Last login date'),
                    $this->_helper->language->translate('Registration date'),
                    $this->_helper->language->translate('IP address'),
                    $this->_helper->language->translate('Referer url'),
                    $this->_helper->language->translate('Google plus profile'),
                    $this->_helper->language->translate('Mobile country code'),
                    $this->_helper->language->translate('Mobile country code value'),
                    $this->_helper->language->translate('Mobile phone'),
                    $this->_helper->language->translate('Notes'),
                    $this->_helper->language->translate('Timezone'),
                    $this->_helper->language->translate('Desktop country code'),
                    $this->_helper->language->translate('Desktop country code value'),
                    $this->_helper->language->translate('Desktop phone'),
                ));
                if($exportResult) {
                    $usersArchive = Tools_System_Tools::zip($exportResult);
                    $this->getResponse()->setHeader('Content-Disposition', 'attachment; filename=' . Tools_Filesystem_Tools::basename($usersArchive))
                        ->setHeader('Content-type', 'application/force-download');
                    readfile($usersArchive);
                    $this->getResponse()->sendResponse();
                }
            }
            exit;
        }
    }
}

