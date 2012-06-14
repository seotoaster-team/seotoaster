<?php

class Tools_Mail_SystemMailWatchdog implements Interfaces_Observer {

	/**
	 * Signup trigger, launches sending of the sign-up emails
	 *
	 */
	const TRIGGER_SIGNUP           = 't_membersignup';

	/**
	 * Notify trigger. Launches sending of the general notification mails
	 *
	 */
    const TRIGGER_NOTIFY            = 't_systemnotification';

	/**
	 * Form sent Nnotification trigger. Launches sending of the form sent notification mails
	 *
	 */
	const TRIGGER_FORMSENT          = 't_feedbackform';

	/**
	 * Password recovery trigger
	 *
	 */
	const TRIGGER_PASSWORDRESET     = 't_passwordreset';

    /**
     * Password change trigger. Launches sending of mails
     */
    const TRIGGER_PASSWORDCHANGE    = 't_passwordchange';

    const RECIPIENT_GUEST           = 'guest';

    const RECIPIENT_MEMBER          = 'member';

    const RECIPIENT_USER            = 'user';

    const RECIPIENT_ADMIN           = 'admin';

    const RECIPIENT_SUPERADMIN      = 'superadmin';

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
            $methodName = '_send'. str_replace(array(' ', '_'), '', ucwords($this->_options['trigger'])) . 'Mail';
            if (method_exists($this, $methodName)) {
                $this->$methodName($object);
            }
        }
    }

	protected function _sendTfeedbackformMail(Application_Model_Models_Form $form) {

        switch ($this->_options['recipient']) {
            case self::RECIPIENT_GUEST:
                $this->_mailer->setMailToLabel('')
                    ->setMailTo('');
            break;
        }
    }


    protected function _sendTmembersignupMail() {

    }

    protected function _sendTpasswordresetMail() {

    }

    protected function _sendTpasswordchangeMail() {

    }

    protected function _sendTsystemnotificationMail() {

    }

    protected function _prepareEmailBody($object) {
        $tmplMessage  = $this->_options['message'];
        $mailTemplate = Application_Model_Mappers_TemplateMapper::getInstance()->find($this->_options['template']);
        if (!empty($mailTemplate)){
            $this->_entityParser->setDictionary(array(
                'emailmessage' => !empty($tmplMessage) ? $tmplMessage : ''
            ));
            //pushing message template to email template and cleaning dictionary
            $mailTemplate = $this->_entityParser->parse($mailTemplate->getContent());
            $this->_entityParser->setDictionary(array());
            return $this->_entityParser->parse($mailTemplate);
        }
        return false;
    }
}
