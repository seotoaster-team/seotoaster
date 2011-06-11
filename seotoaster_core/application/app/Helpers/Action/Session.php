<?php

class Helpers_Action_Session extends Zend_Controller_Action_Helper_Abstract {

	private $_session = null;

	public function init() {
		$this->_session = Zend_Registry::get('session');
	}

	public function getCurrentUser() {
		if(!$this->_session->currentUser) {
			return new Application_Model_Models_User();
		}
		return unserialize($this->_session->currentUser);
	}

	public function setCurrentUser(Application_Model_Models_User $user) {
		$this->_session->currentUser = serialize($user);
	}

	public function getSession() {
		return $this->_session;
	}

	public function  __set($name, $value) {
		$this->_session->$name = $value;
	}

	public function  __get($name) {
		return $this->_session->$name;
	}
}

