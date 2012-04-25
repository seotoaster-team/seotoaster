<?php
/**
 * User: iamne Eugene I. Nezhuta <eugene@seotoaster.com>
 * Date: 4/17/12
 * Time: 1:39 PM
 */

class Application_Model_Models_PasswordRecoveryToken extends Application_Model_Models_Abstract {

	const STATUS_NEW       = 'new';

	const STATUS_USED      = 'used';

	const STATUS_EXPIRED     = 'expired';

	const RESET_URL_TEMPLATE = '%slogin/reset/email/%s/key/%s';

	protected $_tokenHash    = '';

	protected $_saltString   = '_password.recovery_';

	protected $_userId       = 0;

	protected $_status       = self::STATUS_NEW;

	protected $_createdAt    = '';

	protected $_expiredAt    = '';

	public function setCreatedAt($createdAt) {
		$this->_createdAt = $createdAt;
		return $this;
	}

	public function getCreatedAt() {
		return $this->_createdAt;
	}

	public function setExpiredAt($expiredAt) {
		$this->_expiredAt = $expiredAt;
		return $this;
	}

	public function getExpiredAt() {
		return $this->_expiredAt;
	}

	public function setStatus($status) {
		$this->_status = $status;
		return $this;
	}

	public function getStatus() {
		return $this->_status;
	}

	public function generateTokenHash($string = '') {
		$this->_generateTokenHash(($string) ? $string : $this->_saltString);
		return $this->_tokenHash;
	}

	public function getTokenHash() {
		return $this->_tokenHash;
	}

	public function setTokenHash($tokenHash) {
		$this->_tokenHash = $tokenHash;
		return $this;
	}

	public function setUserId($userId) {
		$this->_userId = $userId;
		return $this;
	}

	public function getUserId() {
		return $this->_userId;
	}

	public function setSaltString($saltString) {
		$this->_saltString = $saltString;
		$this->_generateTokenHash();
		return $this;
	}

	public function getSaltString() {
		return $this->_saltString;
	}

	public function getUserEmail() {
		return Application_Model_Mappers_UserMapper::getInstance()->find($this->_userId)->getEmail();
	}

	public function getResetUrl() {
		return $this->_generateResetUrl();
	}

	protected function _generateResetUrl() {
		$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$user          = Application_Model_Mappers_UserMapper::getInstance()->find($this->_userId);
		return sprintf(self::RESET_URL_TEMPLATE, $websiteHelper->getUrl(), $user->getEmail(), $this->_tokenHash);
	}

	protected function _generateTokenHash($string = '') {
		$string           = rand(10, 999) . (($string) ? $string : $this->_saltString) . microtime(true);
		$this->_tokenHash = substr(hash('sha512', $string), 3, 25);
	}
}
