<?php

class Application_Model_Mappers_UserMapper extends Application_Model_Mappers_Abstract {

	protected $_dbTable = 'Application_Model_DbTable_User';

	protected $_model   = 'Application_Model_Models_User';

	public function save($user) {
		if(!$user instanceof Application_Model_Models_User) {
			throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_Models_User instance');
		}
		$data = array(
			'role_id'    => $user->getRoleId(),
			'password'   => md5($user->getPassword()),
			'email'      => $user->getEmail(),
			'full_name'  => $user->getFullName(),
			'last_login' => $user->getLastLogin(),
			'ipaddress'  => $user->getIpaddress()
		);
		if(!$user->getPassword()) {
			unset($data['password']);
		}
		if(null === ($id = $user->getId())) {
			$data['reg_date'] = date('Y-m-d H:i:s', time());
			unset($data['id']);
			return $this->getDbTable()->insert($data);
		}
		else {
			return $this->getDbTable()->update($data, array('id = ?' => $id));
		}
	}

	public function fetchAll($where = null, $order = array()) {
		$where .= (($where) ? ' AND role_id <> "' . Tools_Security_Acl::ROLE_SUPERADMIN . '"' : 'role_id <> "' . Tools_Security_Acl::ROLE_SUPERADMIN . '"');

		return parent::fetchAll($where, 'id ASC');
	}

	public function delete(Application_Model_Models_User $user) {
		$where = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $user->getId());
		$deleteResult = $this->getDbTable()->delete($where);
		$user->notifyObservers();
		return $deleteResult;
	}
}

