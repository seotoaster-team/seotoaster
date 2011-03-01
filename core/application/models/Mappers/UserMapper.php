<?php

class Application_Model_Mappers_UserMapper extends Application_Model_Mappers_Abstract {

	protected $_dbTable = 'Application_Model_DbTable_User';

	public function save($user) {
		if(!$page instanceof Application_Model_Models_User) {
			throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_Models_User instance');
		}
		$data = array(
			//here all user data
		);
		if(null === ($id = $user->getId())) {
			unset($data['id']);
			return $this->getDbTable()->insert($data);
		}
		else {
			return $this->getDbTable()->update($data, array('id = ?' => $id));
		}
	}

    public function find($id, $model) {
		
	}

    public function fetchAll() {
		$resultSet = $this->getDbTable()->fetchAll();
		Zend_Debug::dump($resultSet);
	}

}

