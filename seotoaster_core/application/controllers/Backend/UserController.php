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

    public static $_allowedDefaultAttributes = array('userDefaultTimezone', 'userDefaultPhoneMobileCode', 'remoteLoginRedirect');

	public function init() {
		parent::init();
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_USERS)) {
			$this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
		}
		$this->_helper->AjaxContext()->addActionContexts(array(
			'list'   => 'json',
			'delete' => 'json',
			'load'   => 'json',
            'saveDefaultAttribute' => 'json'
		))->initContext('json');
		$this->view->websiteUrl = $this->_helper->website->getUrl();
        $this->_websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $this->_websiteUrl = $this->_websiteHelper->getUrl();
        $this->_zendDbTable = new Zend_Db_Table();
        $this->_session = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
	}

	public function manageAction() {
        $userMapper = Application_Model_Mappers_UserMapper::getInstance();
	    $usersRoles  = $userMapper->findAllRoles();
        $tranlationUserRoles = array();

	    if(!empty($usersRoles)){
            foreach ($usersRoles as $role){
                $tranlationUserRoles[$role] = $this->_helper->language->translate($role);
            }
        }

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

            $userForm->getElement('fullName')->setValidators(array(
                array(new Zend_Validate_NotEmpty(), true),
                //array(new Zend_Validate_Regex(array('pattern' => '/^[a-zA-Z0-9\s\']*$/u'))
                array(new Zend_Validate_Regex(array('pattern' => '/^[\w\s\']*$/u'))
            )));
            $userForm->getElement('fullName')->getValidator('Zend_Validate_Regex')->setMessage("'%value%' contains characters which are non alphabetic and no digits", Zend_Validate_Regex::NOT_MATCH);
            if($userForm->isValid($this->getRequest()->getParams())) {
                $data       = $userForm->getValues();

                $oldUserEmailAddress = '';
                if(!empty($userId)) {
                    $existedUser = $userMapper->find($userId);
                    if($existedUser instanceof Application_Model_Models_User) {
                        $oldUserEmailAddress = $existedUser->getEmail();
                        $data['lastLogin'] = $existedUser->getLastLogin();
                        $data['ipaddress'] = $existedUser->getIpaddress();
                        $data['notes'] = $existedUser->getNotes();
                        $data['allowRemoteAuthorization'] = $existedUser->getAllowRemoteAuthorization();
                        $data['remoteAuthorizationInfo'] = $existedUser->getRemoteAuthorizationInfo();
                        $data['remoteAuthorizationToken'] = $existedUser->getRemoteAuthorizationToken();
                    }
                }

                $this->_processUser($data, $userId);
                $updateUserInfoStatus = Tools_System_Tools::firePluginMethodByTagName(
                    'userupdate', 'updateUserInfo',
                    array(
                        'userId' => $userId,
                        'oldEmail' => $oldUserEmailAddress,
                        'newEmail' => $data['email']
                    )
                );

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
        $currentUser = $this->_session->getCurrentUser();
        $currentUserRole = $currentUser->getRoleId();
        $this->view->currentLoggedUserRole = $currentUserRole;

        $pnum = (int)filter_var($this->getParam('pnum'), FILTER_SANITIZE_NUMBER_INT);
        $offset = 0;
        if ($pnum) {
            $offset = 10 * ($pnum - 1);
        }

        $select = $this->_zendDbTable->getAdapter()->select()->from('user');

        $by = filter_var($this->getParam('by', 'last_login'), FILTER_SANITIZE_STRING);
        $order = filter_var($this->getParam('order', 'desc'), FILTER_SANITIZE_STRING);
        $searchKey = filter_var($this->getParam('key'), FILTER_SANITIZE_STRING);

        $originalSearchKey = $searchKey;
        if(!empty($tranlationUserRoles) && !empty($searchKey) && in_array($searchKey, $tranlationUserRoles)){
            $searchKey = array_search($searchKey, $tranlationUserRoles);
        }

        if (!in_array($order, array('asc', 'desc'))) {
            $order = 'desc';
        }

        $select = $select->order($by . ' ' . $order);

        $paginatorOrderLink = '/by/' . $by . '/order/' . $order;

        $filterRole = filter_var($this->getParam('filter-by-user-role'), FILTER_SANITIZE_STRING);

        $defaultRole = '0';
        if (!empty($searchKey) || !empty($filterRole)) {
            $where = '';
            if(!empty($filterRole)) {
                $where = $this->_zendDbTable->getAdapter()->quoteInto('role_id = ?', $filterRole);

                $defaultRole = $filterRole;
                $paginatorOrderLink .= '/filter-by-user-role/' . $filterRole;
            }

            if(!empty($searchKey)) {
                if(!empty($where)) {
                    $where .= ' AND ';
                }

                $where .= '('.$this->_zendDbTable->getAdapter()->quoteInto('email LIKE ?', '%'.$searchKey.'%');
                $where .= ' OR ' . $this->_zendDbTable->getAdapter()->quoteInto('full_name LIKE ?', '%'.$searchKey.'%');
                $where .= ' OR ' . $this->_zendDbTable->getAdapter()->quoteInto('role_id LIKE ?', '%'.$searchKey.'%');
                $where .= ' OR ' . $this->_zendDbTable->getAdapter()->quoteInto('last_login LIKE ?', '%'. date("Y-m-d", strtotime($searchKey)).'%');
                $where .= ' OR ' . $this->_zendDbTable->getAdapter()->quoteInto('ipaddress LIKE ?', '%'.$searchKey.'%');
                $where .= ')';
                $paginatorOrderLink .= '/key/' . $searchKey;
            }

            $select->where($where);
        }

        $this->view->userRole = $defaultRole;

        $adapter = new Zend_Paginator_Adapter_DbSelect($select);
        $users = $adapter->getItems($offset, 10);
        $userPaginator = new Zend_Paginator($adapter);
        $userPaginator->setCurrentPageNumber($pnum);
        $userPaginator->setItemCountPerPage(10);


        $pager = $this->view->paginationControl($userPaginator, 'Sliding', 'backend/user/pager.phtml',
            array(
                'urlData' => $this->_websiteUrl . 'backend/backend_user/manage',
                'order'   => $paginatorOrderLink,
                'totalItems' => $userPaginator->getTotalItemCount()
            )
        );

        $orderParam = 'desc';
        if ($order === 'desc') {
            $orderParam = 'asc';
        }

        if (!empty($searchKey)){
            $this->view->orderParam = $orderParam . '/key/' . $searchKey;
        } else {
            $this->view->orderParam = $orderParam;
        }

        $oldMobileFormat = $this->_helper->config->getConfig('oldMobileFormat');
        if (!empty($oldMobileFormat)) {
            $oldMobileFormat = true;
        }

        $configHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
        $userDefaultTimezone = $configHelper->getConfig('userDefaultTimezone');
        $userDefaultMobileCountryCode = $configHelper->getConfig('userDefaultPhoneMobileCode');
        $remoteLoginRedirect = $configHelper->getConfig('remoteLoginRedirect');
        if (empty($remoteLoginRedirect)) {
            $remoteLoginRedirect = '';
        }
        $this->view->userDefaultTimeZone = $userDefaultTimezone;
        $this->view->remoteLoginRedirect = $remoteLoginRedirect;
        $userDeleteCustomMessages = Tools_System_Tools::firePluginMethod('userdelete', 'systemUserDeleteMessage');
        $userDeleteCustomMessage = '';
        $userRolesApplyTo = array();
        if (!empty($userDeleteCustomMessages)) {
            foreach ($userDeleteCustomMessages as $userDeleteCustomMessageData) {
                $userDeleteCustomMessage .= $userDeleteCustomMessageData['message'];
                $userRolesApplyTo = array_merge($userRolesApplyTo, $userDeleteCustomMessageData['userRolesApplyTo']);
            }
        }

        $this->view->userDeleteCustomMessage = $userDeleteCustomMessage;
        $this->view->userRolesApplyTo = $userRolesApplyTo;

        $this->view->by = $by;
        $this->view->order = $order;
        $this->view->key = $originalSearchKey;
        $this->view->pager = $pager;
        $this->view->users = $users;
        $this->view->helpSection = 'users';
        $this->view->userForm = $userForm;
        $this->view->mobileMasks = $listMasksMapper->getListOfMasksByType(Application_Model_Models_MaskList::MASK_TYPE_MOBILE);
        $this->view->desktopMasks = $listMasksMapper->getListOfMasksByType(Application_Model_Models_MaskList::MASK_TYPE_DESKTOP);
        $this->view->mobilePhoneCountryCodes = Tools_System_Tools::getFullCountryPhoneCodesList(true, array(), true);
        $this->view->userDefaultMobileCountryCode = $userDefaultMobileCountryCode;
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
			try {
                $userModel = $userMapper->find($userId);
                if ($userModel instanceof Application_Model_Models_User) {
                    $userMapper->delete($userModel);
                    $userDeleteExternalStatus = Tools_System_Tools::firePluginMethodByTagName('userdelete', 'deleteSystemUser', array('userId' => $userId));
                    $this->_helper->response->success('Removed');
                    exit;
                } else {
                    $this->_helper->response->fail('Can\'t remove user...');
                }

            } catch (Exception $exception) {
                $userDeleteErrorMessage = Tools_System_Tools::firePluginMethod('userdeleteerror', 'systemUserDeleteErrorMessage');

                if(!empty($userDeleteErrorMessage)) {
                    $this->_helper->response->fail(array('userDeleteError' => $userDeleteErrorMessage));
                }

                $this->_helper->response->fail('Can\'t remove user...');
            }

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
                $configHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
                $userDefaultTimezone = $configHelper->getConfig('userDefaultTimezone');
                $userDefaultMobileCountryCode = $configHelper->getConfig('userDefaultPhoneMobileCode');

                $userData = $user->toArray();
                if (empty($userData['timezone'])) {
                    if (empty($userDefaultTimezone)) {
                        $userData['timezone'] = '0';
                    } else {
                        $userData['timezone'] = $userDefaultTimezone;
                    }
                }
                if (empty($userData['desktopCountryCode'])) {
                    if (empty($userDefaultMobileCountryCode)) {
                        $userData['desktopCountryCode'] = 'US';
                    } else {
                        $userData['desktopCountryCode'] = $userDefaultMobileCountryCode;
                    }
                }
                if (empty($userData['mobileCountryCode'])) {
                    if (empty($userDefaultMobileCountryCode)) {
                        $userData['mobileCountryCode'] = 'US';
                    } else {
                        $userData['mobileCountryCode'] = $userDefaultMobileCountryCode;
                    }
                }

                unset($userData['remoteAuthorizationToken']);
                unset($userData['remoteAuthorizationInfo']);

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
            $emailValidator = new Tools_System_CustomEmailValidator();

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
        $data['mobilePhone'] = Tools_System_Tools::cleanNumber($data['mobilePhone']);
        $data['desktopPhone'] = Tools_System_Tools::cleanNumber($data['desktopPhone']);
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

        $roleId = $data['roleId'];
        if ($roleId === Tools_Security_Acl::ROLE_SUPERADMIN) {
            $this->_helper->response->fail($this->_helper->language->translate('not allowed'));
        }

        $currentLoggedUserRole = $this->_helper->session->getCurrentUser()->getRoleId();
        if ($roleId === Tools_Security_Acl::ROLE_ADMIN && ($currentLoggedUserRole !== Tools_Security_Acl::ROLE_ADMIN && $currentLoggedUserRole !== Tools_Security_Acl::ROLE_SUPERADMIN)) {
            $this->_helper->response->fail($this->_helper->language->translate('not allowed'));
        }

        $user       = new Application_Model_Models_User($data);
        $uId = Application_Model_Mappers_UserMapper::getInstance()->save($user);
        $attrNamesArr = filter_var_array($this->getRequest()->getParam('attrName', array()), FILTER_SANITIZE_STRING);
        $attrValuesArr = filter_var_array($this->getRequest()->getParam('attrValue', array()), FILTER_SANITIZE_STRING);
        if ($attrNamesArr) {
            foreach ($attrNamesArr as $key => $value) {
                if(empty($value)) {
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
                    $this->_helper->language->translate('Prefix'),
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
                    $this->_helper->language->translate('Signature'),
                    $this->_helper->language->translate('Subscribed'),
                    $this->_helper->language->translate('Personal calendar url'),
                    $this->_helper->language->translate('Avatar link')
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

    public function getdefaultparamsAction()
    {
        if ($this->getRequest()->isGet() && Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_USERS)) {
            $configHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
            $userDefaultTimezone = $configHelper->getConfig('userDefaultTimezone');
            $userDefaultMobileCountryCode = $configHelper->getConfig('userDefaultPhoneMobileCode');

            $result = array(
                'defaultParams' => array('userDefaultTimezone' => $userDefaultTimezone, 'userDefaultMobileCountryCode' => $userDefaultMobileCountryCode)
            );

            $this->_helper->response->success($result);
        }
    }

    public function savedefaultAction()
    {
        if ($this->getRequest()->isPost() && Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_USERS)) {
            $defaultAttrName = filter_var($this->getRequest()->getParam('defaultAttrName'), FILTER_SANITIZE_STRING);
            $defaultAttrValue = filter_var($this->getRequest()->getParam('defaultAttrValue'), FILTER_SANITIZE_STRING);
            $secureToken = $this->getRequest()->getParam('secureToken', false);
            $tokenValid = Tools_System_Tools::validateToken($secureToken, Tools_System_Tools::ACTION_PREFIX_USERS);
            if (!$tokenValid) {
                $this->_helper->response->fail($this->_helper->language->translate('Invalid token'));
            }
            if (!in_array($defaultAttrName, self::$_allowedDefaultAttributes)) {
                $this->_helper->response->fail($this->_helper->language->translate('Wrong attribute name'));
            }

            Application_Model_Mappers_ConfigMapper::getInstance()->save(array($defaultAttrName => $defaultAttrValue));
            $this->_helper->response->success($this->_helper->language->translate('Saved'));

        }

    }

    public function loginasAction()
    {
        $currentUser = $this->_session->getCurrentUser();
        $currentUserRole = $currentUser->getRoleId();
        if ($currentUserRole !== Tools_Security_Acl::ROLE_ADMIN && $currentUserRole !== Tools_Security_Acl::ROLE_SUPERADMIN) {
            $this->_helper->response->fail($this->_helper->language->translate('Access not allowed'));
        }

        $secureToken = $this->getRequest()->getParam('secureToken', false);
        $userId = $this->getRequest()->getParam('userId', false);
        $tokenValid = Tools_System_Tools::validateToken($secureToken, Tools_System_Tools::ACTION_PREFIX_USERS);
        if (!$tokenValid) {
            $this->_helper->response->fail($this->_helper->language->translate('Invalid token'));
        }

        $userMapper = Application_Model_Mappers_UserMapper::getInstance();
        $userModel = $userMapper->find($userId);
        if (!$userModel instanceof Application_Model_Models_User) {
            $this->_helper->response->fail($this->_helper->language->translate('User not found'));
        }

        $userRoleId = $userModel->getRoleId();
        if ($userRoleId === Tools_Security_Acl::ROLE_SUPERADMIN || $userRoleId === Tools_Security_Acl::ROLE_ADMIN) {
            $this->_helper->response->fail($this->_helper->language->translate('It\'s not allowed to login as this user'));
        }

        $userModel->setPassword('');
        $userModel->setLastLogin(date(Tools_System_Tools::DATE_MYSQL));
        $userModel->setIpaddress($_SERVER['REMOTE_ADDR']);
        $this->_session->setCurrentUser($userModel);
        $userMapper->save($userModel);
        Zend_Session::regenerateId();
        $cacheHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('cache');
        $cacheHelper->clean();

        $this->_helper->response->success('');
    }

}

