<?php

/**
 * Mailer watchdog
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Mail_Watchdog implements Interfaces_Observer {

	const TRIGGER_SIGNUP = 'signup';

	private $_options = array();

	public function __construct($options = array()) {
		$this->_options = $options;
	}

	public function notify($object) {
		if(isset($this->_options['trigger'])) {
			switch ($this->_options['trigger']) {
				case self::TRIGGER_SIGNUP:
					$this->_sendSignupMails();
				break;
			}
		}
	}

	private function _sendSignupMails() {
		Tools_Mail_Tools::sendSignupEmail();
		Tools_Mail_Tools::sendMailToSiteOwner(self::TRIGGER_SIGNUP);
	}
}

