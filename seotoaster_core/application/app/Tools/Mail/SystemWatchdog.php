<?php

class Tools_Mail_SystemWatchdog implements Interfaces_Observer {

	/**
	 * Signup trigger, launches sending of the sign-up emails
	 *
	 */
	const TRIGGER_SIGNUP           = 'signupMail';

	/**
	 * Notify trigger. Launches sending of the general notification mails
	 *
	 */
    const TRIGGER_NOTIFY            = 'notifyMail';

	/**
	 * Form sent Nnotification trigger. Launches sending of the form sent notification mails
	 *
	 */
	const TRIGGER_FORMSENT          = 'formsentMail';

	/**
	 * Password recovery trigger
	 *
	 */
	const TRIGGER_PASSWORDRETRIEVE = 'passwordretrieveMail';

	private $_options      = array();

	private $_object       = null;

	private $_entityParser = null;

	public function __construct($options = array()) {
		$this->_entityParser = new Tools_Content_EntityParser();
		$this->_options = $options;
	}

	public function notify($object) {
		$this->_object = $object;
		if(isset($this->_options['trigger'])) {
			$mailTriggerHandler = '_send' . ucfirst($this->_options['trigger']);
			if(method_exists($this, $mailTriggerHandler)) {
				$this->$mailTriggerHandler();
			}
		}
	}

	protected function _sendPasswordretrieveMail() {
		$this->_entityParser->objectToDictionary($this->_object);
		$mailer   = new Tools_Mail_Mailer();
		$mailer->setMailFrom('support@seotoaster.com');
		$mailer->setMailFromLabel('Seotoaster support team');
		$mailer->setMailTo($this->_object->getUserEmail());
		$mailer->setBody('<a href="' . $this->_object->getResetUrl() . '">' . $this->_object->getResetUrl() . '</a>');
		$mailer->setSubject('[Seotoaster] Please reset your password');
		$mailer->send();
	}

	protected function _sendSignupMail() {

	}

	protected function _sendNotifyMail() {

	}

	protected function _sendFormsentMail() {

	}

	protected function _notifySiteOwner() {

	}

	private function _sendMail($params) {
		$mailer = new Tools_Mail_Mailer();
		$mailer->setMailTo($params['mailTo']);
		$mailer->setMailFrom($params['mailFrom']);
		$mailer->setSubject($params['subject']);
		$mailer->setBody($params['body']);
		return $mailer->send();
	}
}
