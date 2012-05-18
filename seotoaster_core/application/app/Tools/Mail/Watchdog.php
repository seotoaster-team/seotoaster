<?php

/**
 * Mailer watchdog
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Mail_Watchdog implements Interfaces_Observer {

	const OBSERVER_LIST_PROP = 'emailTriggers';

	/**
	 * Signup trigger, launches sending of the sign-up emails
	 * @deprecated
	 */
	const TRIGGER_SIGNUP = 'signup';

	/**
	 * Notify trigger. Launches sending of the general notification mails
	 * @deprecated
	 */
    const TRIGGER_NOTIFY = 'notify';

	/**
	 * Form sent Nnotification trigger. Launches sending of the form sent notification mails
	 * @deprecated
	 */
	const TRIGGER_FORMSENT_NOTIFY = 'formsent';

	private $_options = array();

	/**
	 * @var null|array List of available triggers (cached)
	 */
	private $_triggers = null;

	public function __construct($options = array()) {
		$this->_cacheHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('cache');
		$this->_initTriggers();
		$this->_options = $options;
	}

	public function notify($object) {
		if(isset($this->_options['trigger'])) {
			if ($this->_triggers === null) {
				$this->_initTriggers(true);
			}
			$triggerName = strtolower($this->_options['trigger']);
			$activeTriggers = array_filter($this->_triggers, function($trig) use ($triggerName) {
				return ($trig['trigger_name'] === $triggerName && $trig['enabled'] === Application_Model_Mappers_EmailTriggersMapper::TRIGGER_STATUS_ENABLED);
			});
			if (!empty($activeTriggers)){
				foreach($activeTriggers as $trigger) {
					if (class_exists($trigger['observer'])) {
						$actions = Application_Model_Mappers_EmailTriggersMapper::getInstance()->findByTriggerName($this->_options['trigger'])->toArray();
                        array_walk($actions, function($action, $key, $context){
							$observer = new $context['observer'](array_merge($action, $context['options']));
							$observer->notify($context['object']);
						}, array('observer' => $trigger['observer'], 'object' => $object, 'options' => $this->_options));
					}
				}
			}
		}
	}

	/**
	 * @deprecated
	 */
	private function _sendSignupMails() {
		Tools_Mail_Tools::sendSignupEmail();
		Tools_Mail_Tools::sendMailToSiteOwner(self::TRIGGER_SIGNUP);
	}

	/**
	 * @deprecated
	 */
    private function _sendNotificationMails() {
		Tools_Mail_Tools::sendMailToSiteOwner(self::TRIGGER_NOTIFY);
    }

	/**
	 * @deprecated
	 */
	private function _sendFormsentNotificationMails() {
		Tools_Mail_Tools::sendMailToSiteOwner(self::TRIGGER_FORMSENT_NOTIFY);
    }


	private function _initTriggers($force = false) {
		if (null === ($triggers = $this->_cacheHelper->load(__CLASS__)) || $force) {
			$triggers = Application_Model_Mappers_EmailTriggersMapper::getInstance()->getTriggers();
			$this->_cacheHelper->save(__CLASS__, $triggers, array('plugins'));
		}
		$this->_triggers = $triggers;
	}
}

