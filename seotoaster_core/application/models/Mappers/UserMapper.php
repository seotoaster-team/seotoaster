<?php
/**
 * Class Application_Model_Mappers_UserMapper
 * @method static Application_Model_Mappers_UserMapper getInstance() getInstance() Returns an instance of itself
 * @method Application_Model_DbTable_User getDbTable() Returns an instance of corresponding DbTable
 */
class Application_Model_Mappers_UserMapper extends Application_Model_Mappers_Abstract {

	protected $_dbTable = 'Application_Model_DbTable_User';

	protected $_model   = 'Application_Model_Models_User';

	public function save($user) {
		if(!$user instanceof Application_Model_Models_User) {
			throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_Models_User instance');
		}
		$data = array(
			'role_id'                    => $user->getRoleId(),
			'password'                   => md5($user->getPassword()),
			'email'                      => $user->getEmail(),
			'full_name'                  => $user->getFullName(),
			'last_login'                 => $user->getLastLogin(),
			'ipaddress'                  => $user->getIpaddress(),
            'gplus_profile'              => $user->getGplusProfile(),
            'mobile_phone'               => $user->getMobilePhone(),
            'notes'                      => $user->getNotes(),
            'timezone'                   => $user->getTimezone(),
            'mobile_country_code'        => $user->getMobileCountryCode(),
            'mobile_country_code_value'  => $user->getMobileCountryCodeValue(),
            'desktop_phone'              => $user->getDesktopPhone(),
            'desktop_country_code'       => $user->getDesktopCountryCode(),
            'desktop_country_code_value' => $user->getDesktopCountryCodeValue(),
            'signature'                  => $user->getSignature(),
            'subscribed'                 => $user->getSubscribed(),
            'voip_phone'                 => $user->getVoipPhone(),
            'prefix'                     => $user->getPrefix(),
            'allow_remote_authorization' => $user->getAllowRemoteAuthorization(),
            'remote_authorization_info'  => $user->getRemoteAuthorizationInfo(),
            'remote_authorization_token' => $user->getRemoteAuthorizationToken(),
            'personal_calendar_url'      => $user->getPersonalCalendarUrl(),
            'avatar_link'                => $user->getAvatarLink()
		);
		if(!$user->getPassword()) {
			unset($data['password']);
		}

        if ($user->getAttributes()) {
            Application_Model_Mappers_UserMapper::getInstance()->saveUserAttributes($user);
        }

		if(null === ($id = $user->getId())) {
			$data['reg_date'] = date('Y-m-d H:i:s', time());
			unset($data['id']);
			if ($user->getReferer()){
				$data['referer'] = $user->getReferer();
			}
			return $this->getDbTable()->insert($data);
		}
		else {
			return $this->getDbTable()->update($data, array('id = ?' => $id));
		}
	}

	public function fetchAll($where = null, $order = array(), $withSuperAdmin = false) {
        if(!$withSuperAdmin) {
		    $where .= (($where) ? ' AND role_id <> "' . Tools_Security_Acl::ROLE_SUPERADMIN . '"' : 'role_id <> "' . Tools_Security_Acl::ROLE_SUPERADMIN . '"');
        }

		return parent::fetchAll($where, 'id ASC');
	}

	public function findByEmail($email) {
		$where = $this->getDbTable()->getAdapter()->quoteInto("email = ?", $email);
		$row   = $this->getDbTable()->fetchAll($where)->current();
		if(!$row) {
			return null;
		}
		return new $this->_model($row->toArray());
	}

    public function findByRole($role) {
        $where = $this->getDbTable()->getAdapter()->quoteInto("role_id = ?", $role);
        $row   = $this->getDbTable()->fetchAll($where)->current();
        if(!$row) {
            return null;
        }
        return new $this->_model($row->toArray());
    }

    public function findAllRoles() {
        $select = $this->getDbTable()->getAdapter()->select()->distinct()->from('user', array(
            'role_id', 'role_id'
        ));
        $users = $this->getDbTable()->getAdapter()->fetchPairs($select);
        return $users;
    }

	public function delete(Application_Model_Models_User $user) {
		$where = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $user->getId());
		$deleteResult = $this->getDbTable()->delete($where);
		$user->notifyObservers();
		return $deleteResult;
	}

    public function loadUserAttributes(Application_Model_Models_User $user) {
        $attributes = array();
        if ($user->getId()) {
            $select = $this->getDbTable()->getAdapter()->select()->from('user_attributes', array('attribute', 'value'))
                    ->where('user_id = ?', $user->getId());

            $data = $this->getDbTable()->getAdapter()->fetchPairs($select);
            if (!is_null($data)) {
                $attributes = $data;
            }
        }

        return $user->setAttributes($attributes);
    }

    public function fetchUniqueAttributesNames()
    {
        $select = $this->getDbTable()->getAdapter()->select()->distinct()->from('user_attributes', array('attribute'));
        $usersAttributes = $this->getDbTable()->getAdapter()->fetchCol($select);
        return $usersAttributes;

    }

    public function saveUserAttributes(Application_Model_Models_User $user) {
        $paramsCount = func_num_args();

        if ($paramsCount === 1) {
            list($user) = func_get_args();
            $attribs = $user->getAttributes();
        } elseif ($paramsCount === 2) {
            list($user, $attribs) = func_get_args();
        } elseif ($paramsCount === 3) {
            $params = func_get_args();
            $user = array_shift($params);
            $attribs = array( $params[0] => $params[1] );
            unset($params);
        }

        $dbTable = new Zend_Db_Table('user_attributes');

        $userId = $user->getId();
        $dbTable->delete(array('user_id = ?' => $userId));

        if (is_array($attribs) && !empty($attribs)) {
            foreach($attribs as $name => $value){
                $dbTable->insert(array(
                    'user_id' => $userId,
                    'attribute' => $name,
                    'value' => $value
                ));
            }
            $user->setAttributes($attribs);
        }

        return $user;
    }

    /**
     * Get user List
     */
    public function getUserList()
    {
        $select = $this->getDbTable()->getAdapter()->select()->from('user', array(
            'email',
            'role_id',
            'prefix',
            'full_name',
            'last_login',
            'reg_date',
            'ipaddress',
            'referer',
            'gplus_profile',
            'mobile_country_code',
            'mobile_country_code_value',
            'mobile_phone',
            'notes',
            'timezone',
            'desktop_country_code',
            'desktop_country_code_value',
            'desktop_phone',
            'signature',
            'subscribed',
            'personal_calendar_url',
            'avatar_link'
        ))
            ->where('role_id <> "' . Tools_Security_Acl::ROLE_SUPERADMIN . '"');

        $select->order('role_id ASC');

        $users = $this->getDbTable()->getAdapter()->fetchAll($select);
        return $users;
    }

    /**
     * @param $token
     * @return Application_Model_Models_User
     */
    public function findByRemoteAuthToken($token)
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto('remote_authorization_token = ?', $token);
        return $this->_findWhere($where);
    }
}

