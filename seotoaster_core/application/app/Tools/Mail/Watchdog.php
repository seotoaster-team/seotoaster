<?php

/**
 * Mailer watchdog
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Mail_Watchdog implements Interfaces_Observer {

	/**
	 * Signup trigger, launches sending of the sign-up emails
	 *
	 */
	const TRIGGER_SIGNUP = 'signup';

	/**
	 * Notify trigger. Launches sending of the general notification mails
	 *
	 */
    const TRIGGER_NOTIFY = 'notify';

	/**
	 * Form sent Nnotification trigger. Launches sending of the form sent notification mails
	 *
	 */
	const TRIGGER_FORMSENT_NOTIFY = 'formsent';

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
                case self::TRIGGER_NOTIFY:
					$this->_sendNotificationMails();
				break;
				case self::TRIGGER_FORMSENT_NOTIFY:
					$this->_sendFormsentNotificationMails();
				break;
			}
		}
	}

	private function _sendSignupMails() {
		Tools_Mail_Tools::sendSignupEmail();
		Tools_Mail_Tools::sendMailToSiteOwner(self::TRIGGER_SIGNUP);
	}

    private function _sendNotificationMails() {
		Tools_Mail_Tools::sendMailToSiteOwner(self::TRIGGER_NOTIFY);
    }

	 private function _sendFormsentNotificationMails() {
		Tools_Mail_Tools::sendMailToSiteOwner(self::TRIGGER_FORMSENT_NOTIFY);
    }
}

