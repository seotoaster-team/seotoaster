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

	private $_options       = array();

	private $_entityParser  = null;

    private $_mailer        = null;

    private $_websiteHelper = null;

    private $_translator    = null;

    private $_configHelper  = null;

	public function __construct($options = array()) {
		$this->_entityParser  = new Tools_Content_EntityParser();
        $this->_mailer        = Tools_Mail_Tools::initMailer();
		$this->_options       = $options;
        $this->_websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $this->_translator    = Zend_Controller_Action_HelperBroker::getStaticHelper('language');
        $this->_configHelper  = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
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
        $this->_sendTfeedbackformMailContact($form);
        $this->_sendTfeedbackformMailReply($form);
    }


    protected function _sendTfeedbackformMailReply(Application_Model_Models_Form $form) {
        $this->_mailer             = Tools_Mail_Tools::initMailer();
        $formDetails               = $this->_options['data'];
        $formReplyMessage          = $form->getReplyText();
        $this->_options['message'] = ($formReplyMessage) ? $formReplyMessage : $this->_translator->translate('Thank you for your submission');
        $this->_mailer->setMailToLabel($formDetails['name'])
            ->setMailTo($formDetails['email']);
        if(($replyTemplate = $form->getReplyMailTemplate()) != null) {
            $this->_options['template'] = $replyTemplate;
        }
        if(($mailBody = $this->_prepareEmailBody()) !== false) {
            $this->_mailer->setBody($this->_entityParser->parse($mailBody));
        } else {
            $this->_mailer->setBody($this->_translator->translate('Thank you for your feedback'));
        }
        $this->_mailer->setSubject($form->getReplySubject())
            ->setMailFromLabel($form->getReplyFromName())
            ->setMailFrom($form->getReplyFrom());
        return $this->_mailer->send();
    }

    protected function _sendTfeedbackformMailContact(Application_Model_Models_Form $form) {
        $formDetails = $this->_options['data'];
        unset($formDetails['controller']);
        unset($formDetails['action']);
        unset($formDetails['module']);
        unset($formDetails['formName']);
        $this->_mailer->setMailToLabel($form->getContactEmail())
            ->setMailTo($form->getContactEmail());
        $mailBody = '{form:details}';
        $formDetailsHtml = '';
        foreach($formDetails as $name => $value) {
            if(!$value) {
                continue;
            }
            $formDetailsHtml .= $name . ': ' . (is_array($value) ? implode(', ', $value) : $value) . '<br />';
        }
        $this->_entityParser->setDictionary(array(
            'form:details' => $formDetailsHtml
        ));
        $this->_mailer->setBody($this->_entityParser->parse($mailBody));
        $this->_mailer->setSubject($this->_translator->translate('New form submited'))
            ->setMailFromLabel($this->_translator->translate('Notifications @ ') . $this->_websiteHelper->getUrl())
            ->setMailFrom($this->_configHelper->getConfig('adminEmail'));
        return $this->_mailer->send();
    }

    protected function _sendTmembersignupMail(Application_Model_Models_User $user) {
        switch ($this->_options['recipient']) {
            case self::RECIPIENT_MEMBER:

            break;
            case self::RECIPIENT_SUPERADMIN:

            break;
        }
    }

    protected function _sendTpasswordresetMail() {

    }

    protected function _sendTpasswordchangeMail() {

    }

    protected function _sendTsystemnotificationMail() {

    }

    protected function _prepareEmailBody() {
        $tmplMessage  = $this->_options['message'];
        $mailTemplate = Application_Model_Mappers_TemplateMapper::getInstance()->find($this->_options['template']);
        if (!empty($mailTemplate)){
            $this->_entityParser->setDictionary(array(
                'emailmessage' => !empty($tmplMessage) ? $tmplMessage : ''
            ));
            //pushing message template to email template and cleaning dictionary
            $mailTemplate = $this->_entityParser->parse($mailTemplate->getContent());
            $this->_entityParser->setDictionary(array());
            $mailTemplate = $this->_entityParser->parse($mailTemplate);

            $themeData = Zend_Registry::get('theme');
            $extConfig = Zend_Registry::get('extConfig');
            $parserOptions = array(
                'websiteUrl'   => $this->_websiteHelper->getUrl(),
                'websitePath'  => $this->_websiteHelper->getPath(),
                'currentTheme' => $extConfig['currentTheme'],
                'themePath'    => $themeData['path'],
            );
            $parser = new Tools_Content_Parser($mailTemplate, array(), $parserOptions);
            return $parser->parseSimple();
        }
        return false;
    }
}
