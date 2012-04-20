<?php
/**
 * User: iamne Eugene I. Nezhuta <eugene@seotoaster.com>
 * Date: 4/17/12
 * Time: 2:02 PM
 */

class Application_Model_Mappers_PasswordRecoveryMapper extends Application_Model_Mappers_Abstract {

	protected $_dbTable = 'Application_Model_DbTable_PasswordResetLog';

	protected $_model   = 'Application_Model_Models_PasswordRecoveryToken';

	public function save($recoveryToken) {

		if(!$recoveryToken instanceof Application_Model_Models_PasswordRecoveryToken) {
			throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_Models_PasswordRecoveryToken instance');
		}

		$data = array(
			'token_hash'  => $recoveryToken->getTokenHash(),
			'user_id'     => $recoveryToken->getUserId(),
			'status'      => $recoveryToken->getStatus(),
			'expired_at'  => $recoveryToken->getExpiredAt()
		);

		if(false === ($exists = $this->_tokenForUserExists($recoveryToken->getUserId()))) {
			$result = $this->getDbTable()->insert($data);
		} else {
			$result = $this->getDbTable()->update($data, array('id = ?' => $exists->getId()));
		}
		$recoveryToken->notifyObservers();
		return $result;
	}

	public function findByToken($token) {
		$where = $this->getDbTable()->getAdapter()->quoteInto("token_hash = ?", $token);
		return new $this->_model($this->getDbTable()->fetchAll($where)->current()->toArray());
	}

	public function findByTokenAndMail($token, $email) {
		$user = Application_Model_Mappers_UserMapper::getInstance()->findByEmail($email);
		if(!$user) {
			return null;
		}
		$where  = $this->getDbTable()->getAdapter()->quoteInto("token_hash = ?", $token);
		$where .= $this->getDbTable()->getAdapter()->quoteInto(" AND user_id = ?", $user->getId());
		$row    = $this->getDbTable()->fetchAll($where)->current();
		if(!$row) {
			return null;
		}
		return new $this->_model($row->toArray());
	}

	protected function _tokenForUserExists($userId) {
		$existedTokens = $this->fetchAll('user_id = ' . $userId);
		if($existedTokens && is_array($existedTokens)) {
			return $existedTokens[0];
		}
		return false;
	}

}
