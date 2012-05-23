<?php

class Tools_Mail_SystemMailWatchdog implements Interfaces_Observer {

	/**
	 * Signup trigger, launches sending of the sign-up emails
	 *
	 */
	const TRIGGER_SIGNUP           = 'member signup';

	/**
	 * Notify trigger. Launches sending of the general notification mails
	 *
	 */
    const TRIGGER_NOTIFY            = 'system notify';

	/**
	 * Form sent Nnotification trigger. Launches sending of the form sent notification mails
	 *
	 */
	const TRIGGER_FORMSENT          = 'feedback form sent';

	/**
	 * Password recovery trigger
	 *
	 */
	const TRIGGER_PASSWORDRETRIEVE = 'passwordretrieve';

	private $_options      = array();

	private $_object       = null;

	private $_entityParser = null;

    private $_mailer       = null;

	public function __construct($options = array()) {
		$this->_entityParser = new Tools_Content_EntityParser();
        $this->_mailer       = Tools_Mail_Tools::initMailer();
		$this->_options      = $options;
	}

    public function notify($object) {
        if (!$object){
            return false;
        }
        if (isset($this->_options['trigger'])){
            $methodName = '_send'. str_replace(' ', '', ucwords($this->_options['trigger'])) . 'Mail';
            if (method_exists($this, $methodName)){
                $this->$methodName($object);
            }
        }
    }

	protected function _sendFeedbackFormSentMail(Application_Model_Models_Form $form) {
        $triggerAction = $this->_invokeTriggerAction($this->_options['trigger']);
    }


    private function _invokeTriggerAction($triggerName) {
        $triggerAction = Application_Model_Mappers_EmailTriggersMapper::getInstance()->findByTriggerName($triggerName);
        return (!$triggerAction) ? null : new Application_Model_Models_TriggerAction($triggerAction->current()->toArray());
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

    private function _sendMail($params) {
		$mailer = new Tools_Mail_Mailer();
		$mailer->setMailTo($params['mailTo']);
		$mailer->setMailFrom($params['mailFrom']);
		$mailer->setSubject($params['subject']);
		$mailer->setBody($params['body']);
		return $mailer->send();
	}
}
