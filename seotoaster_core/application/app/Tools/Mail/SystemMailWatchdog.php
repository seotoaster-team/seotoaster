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
     * User change atttibutes
     *
     */
    const TRIGGER_USERCHANGEATTR     = 't_userchangeattr';

    /**
     * User invitation
     *
     */
    const TRIGGER_USERINVITATION     = 't_userinvitation';

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
                return $this->$methodName($object);
            }
        }
    }

	protected function _sendTfeedbackformMail(Application_Model_Models_Form $form) {
        if(!isset($this->_options['recipient'])){
            $contactMailSent = $this->_sendTfeedbackformMailContact($form);
            $replyMailSent   = $this->_sendTfeedbackformMailReply($form);
        } else {
            $this->_sendTfeedbackformMailAdditionalContact($form);
        }
        return ($contactMailSent && $replyMailSent);
    }

    protected function _sendTfeedbackformMailAdditionalContact(Application_Model_Models_Form $form){
        $this->_mailer = Tools_Mail_Tools::initMailer();
        $userMapper = Application_Model_Mappers_UserMapper::getInstance();
        $where = $userMapper->getDbTable()->getAdapter()->quoteInto("role_id = ?", Tools_Security_Acl::ROLE_ADMIN);
        $admins = $userMapper->fetchAll($where);
        $adminBccArray = array();
        switch ($this->_options['recipient']) {
            case self::RECIPIENT_ADMIN:
                if(!empty($admins)){
                    foreach($admins as $key=>$admin){
                        if($key == 0){
                            $this->_mailer->setMailToLabel($admin->getFullName())
                                ->setMailTo($admin->getEmail());
                        }else{
                            array_push($adminBccArray, $admin->getEmail());
                        }
                    }
                    if(!empty($adminBccArray)){
                        $this->_mailer->setMailBcc($adminBccArray);
                    }
                }else{
                    return;
                }
            break;
            default: 
                return;
            break;
        }

        return $this->_sendFeedBackForm($form);
    }

    protected function _sendTfeedbackformMailReply(Application_Model_Models_Form $form) {
	    if (!$form->getReplyEmail()) {
            $this->_mailer = Tools_Mail_Tools::initMailer();
            $formDetails = $this->_options['data'];
            $formReplyMessage = $form->getReplyText();
            $this->_mailer->setMailToLabel($formDetails['name'])
                ->setMailTo($formDetails['email']);
            if (($replyTemplate = $form->getReplyMailTemplate()) != null) {
                $this->_options['template'] = $replyTemplate;
            }

            if (empty($formReplyMessage)) {
                $this->_options['message'] = $this->_translator->translate('Thank you for your submission');
            } else {
                $this->_options['message'] = $formReplyMessage;
            }

            $pageUrl = str_replace($this->_websiteHelper->getUrl(), '', $formDetails['formUrl']);
            $pageModel = Application_Model_Mappers_PageMapper::getInstance()->findByUrl($pageUrl);
            $pageId = 0;
            if ($pageModel instanceof Application_Model_Models_Page) {
               $pageId = $pageModel->getId();
            }

            if (($mailBody = $this->_prepareEmailBody($pageId)) !== false) {
                $this->_addToDictionaryLexemes($formDetails);
                $this->_mailer->setBody($this->_entityParser->parse($mailBody));
            } else {
                $this->_mailer->setBody($this->_translator->translate('Thank you for your feedback'));
            }

            $this->_mailer->setSubject($form->getReplySubject())
               ->setMailFromLabel($form->getReplyFromName())
               ->setMailFrom($form->getReplyFrom());

            return $this->_mailer->send();
        }

        return true;
    }

    protected function _sendTfeedbackformMailContact(Application_Model_Models_Form $form) {
        $emails = $this->_prepareEmail($form->getContactEmail());
        $this->_mailer->setMailToLabel($emails[0])
            ->setMailTo($emails[0]);
        if(count($emails) > 1){
            array_shift($emails);
            $this->_mailer->setMailBcc($emails);
        }

        return $this->_sendFeedBackForm($form);
    }

    private function _sendFeedBackForm(Application_Model_Models_Form $form)
    {
        $formDetails = $this->_cleanFormData($this->_options['data']);

        $adminTemplateEmail = $form->getAdminMailTemplate();

        if (!empty($adminTemplateEmail)) {
            $this->_options['template'] = $adminTemplateEmail;
        }

        $formAdminMessage  = $form->getAdminText();

        if (!empty($formAdminMessage)) {
            $this->_options['message'] = $formAdminMessage;
        }

        $formUrl = '';
        if (isset($formDetails['formUrl'])) {
            $formUrl = $formDetails['formUrl'];
            unset($formDetails['formUrl']);
        }

        if (($mailBody = $this->_prepareEmailBody()) === false) {
            $mailBody = '{form:details}';
        }

        $formDetailsHtml = $this->_prepareFormDetailsHtml($formDetails);

        $this->_entityParser->setDictionary(array(
            'form:details' => $formDetailsHtml
        ));

        $formDetails['formUrl'] = $formUrl;
        $this->_addToDictionaryLexemes($formDetails);
        $mailBody = $this->_entityParser->parse($mailBody);

        if ($formUrl && empty($adminTemplateEmail)) {
            $mailBody .= '<div style="background:#eee;padding:10px;">' . $this->_translator->translate('This form was submitted from') . ': <a href="' . $formUrl . '">' . $formUrl . '</a></div>';
        }

        if (isset($this->_options['attachment']) && is_array($this->_options['attachment']) && !empty($this->_options['attachment'])) {
            $this->_mailer->addAttachment($this->_options['attachment']);
        }
        $this->_mailer->setBody($mailBody);

        $adminFormSubject = $form->getAdminSubject();
        if (empty($adminFormSubject)) {
            $senderFullName = (isset($formDetails['lastname'])) ? $formDetails['name'] . ' ' . $formDetails['lastname'] : $formDetails['name'];
            $adminFormSubject = $this->_translator->translate('New') . ' ' . $form->getName() . ' ' . $this->_translator->translate('form submitted from') . ' ' . $senderFullName;
        }

        $adminFromName = $form->getAdminFromName();
        if (empty($adminFormSubject)) {
            $adminFromName = $this->_translator->translate('Notifications @ ') . $this->_websiteHelper->getUrl();
        }

        $adminFromEmail = $form->getAdminFrom();
        if (empty($adminFromEmail)) {
            $adminFromEmail = $this->_configHelper->getConfig('adminEmail');
        }

        $this->_mailer->setSubject($adminFormSubject)
            ->setMailFromLabel($adminFromName)
            ->setMailFrom($adminFromEmail);

        return $this->_mailer->send();
    }

    protected function _sendTmembersignupMail(Application_Model_Models_User $user) {
        switch ($this->_options['recipient']) {
            case self::RECIPIENT_MEMBER:
                $this->_mailer->setMailToLabel($user->getFullName())
                    ->setMailTo($user->getEmail())
                    ->setSubject(isset($this->_options['subject']) ? $this->_options['subject'] : $this->_translator->translate('Welcome!'));
                //create link for password generation and send e-mail to the user
                $resetToken = Tools_System_Tools::saveResetToken($user->getEmail(), $user->getId(), '+1 week');
            break;
            case self::RECIPIENT_SUPERADMIN:
                $superAdmin = Application_Model_Mappers_UserMapper::getInstance()->findByRole(Tools_Security_Acl::ROLE_SUPERADMIN);
                $this->_mailer->setMailToLabel($superAdmin->getFullName())
                    ->setMailTo($superAdmin->getEmail())
                    ->setSubject(isset($this->_options['subject']) ? $this->_options['subject'] : $this->_translator->translate('New user is registered!'));
            break;
            case self::RECIPIENT_ADMIN:
                $adminBccArray = array();
                $userMapper = Application_Model_Mappers_UserMapper::getInstance();
                $where = $userMapper->getDbTable()->getAdapter()->quoteInto("role_id = ?", Tools_Security_Acl::ROLE_ADMIN);
                $admins = $userMapper->fetchAll($where);
                if(!empty($admins)){
                    foreach($admins as $key=>$admin){
                        if($key == 0){
                            $this->_mailer->setMailTo($admin->getEmail());
                        }else{
                            array_push($adminBccArray, $admin->getEmail());
                        }
                    }
                    if(!empty($adminBccArray)){
                        $this->_mailer->setSubject(isset($this->_options['subject']) ? $this->_options['subject'] : $this->_translator->translate('New user is registered!'));
                        $this->_mailer->setMailBcc($adminBccArray);
                    }
                }else{
                    return;
                }
                break;
        }
        
        if(($mailBody = $this->_prepareEmailBody()) == false) {
            $mailBody = $this->_options['message'];
        }
        $this->_entityParser->objectToDictionary($user);
        if ($resetToken instanceof Application_Model_Models_PasswordRecoveryToken) {
            $this->_entityParser->addToDictionary( array(
                'user:passwordLink' => '<a href="' . $resetToken->getResetUrl() . '/new/user">link</a>',
                'user:passwordLinkRaw'  => $resetToken->getResetUrl()
            ));
        }
        if(!isset($this->_options['from'])) {
            $this->_options['from'] = Application_Model_Mappers_UserMapper::getInstance()->findByRole(Tools_Security_Acl::ROLE_SUPERADMIN)->getEmail();
        }

        $wicEmail = $this->_configHelper->getConfig('wicEmail');
        $this->_entityParser->setDictionary( array(
            'widcard:BizEmail' => !empty($wicEmail) ? $wicEmail : $this->_configHelper->getConfig('adminEmail')
        ));

        return $this->_mailer->setMailFrom($this->_entityParser->parse($this->_options['from']))
            ->setBody($this->_entityParser->parse($mailBody))
            ->send();
    }

    protected function _sendTpasswordresetMail(Application_Model_Models_PasswordRecoveryToken $token) {
	    $mailBody = $this->_prepareEmailBody();

        $wicEmail = $this->_configHelper->getConfig('wicEmail');
	    $this->_entityParser->setDictionary(
		    array(
			    'reset:link' => '<a href="' . $token->getResetUrl() . '">' . $token->getResetUrl() . '</a>',
			    'reset:url'  => $token->getResetUrl(),
                'widcard:BizEmail' => !empty($wicEmail) ? $wicEmail : $this->_configHelper->getConfig('adminEmail')
		    )
	    );

	    $mailer   = Tools_Mail_Tools::initMailer();
        $subject = ($this->_options['subject'] == '') ? $this->_websiteHelper->getUrl() .' '.$this->_translator->translate('Please reset your password'):$this->_options['subject'];
	    $mailer->setMailFrom($this->_entityParser->parse($this->_options['from']));
        $mailer->setMailTo($token->getUserEmail());
        $mailer->setBody($this->_entityParser->parse($mailBody));
        $mailer->setSubject($subject);
	    $status = $mailer->send();
        return $status;
    }

    protected function _sendTpasswordchangeMail(Application_Model_Models_PasswordRecoveryToken $token) {
	    $mailBody = $this->_prepareEmailBody();

        $wicEmail = $this->_configHelper->getConfig('wicEmail');
        $this->_entityParser->setDictionary(array(
            'widcard:BizEmail' => !empty($wicEmail) ? $wicEmail : $this->_configHelper->getConfig('adminEmail')
        ));

        $subject = ($this->_options['subject'] == '') ? $this->_websiteHelper->getUrl().' '.$this->_translator->translate('Your password successfully changed'):$this->_options['subject'];
        $this->_mailer->setMailFrom($this->_entityParser->parse($this->_options['from']))
               ->setMailTo($token->getUserEmail())
		       ->setBody($mailBody)
	           ->setSubject($subject);
        return $this->_mailer->send();
    }

    protected function _sendTuserchangeattrMail(Application_Model_Models_User $user) {
        $subject = ($this->_options['subject'] == '') ? $this->_websiteHelper->getUrl().' '.$this->_translator->translate('User attribute changed'):$this->_options['subject'];

        $wicEmail = $this->_configHelper->getConfig('wicEmail');
        $this->_entityParser->setDictionary( array(
            'widcard:BizEmail' => !empty($wicEmail) ? $wicEmail : $this->_configHelper->getConfig('adminEmail')
        ));

        $this->_mailer->setMailFrom($this->_entityParser->parse($this->_options['from']))
            ->setBody($this->_prepareEmailBody())
            ->setSubject($subject);
        $this->_entityParser->objectToDictionary($user);
        $this->_entityParser->addToDictionary($user->getAttributes());
        $this->_mailer->setBody($this->_entityParser->parse($this->_mailer->getBody()));
        switch ($this->_options['recipient']) {
            case self::RECIPIENT_ADMIN:
                $adminBccArray = array();
                $userMapper = Application_Model_Mappers_UserMapper::getInstance();
                $where = $userMapper->getDbTable()->getAdapter()->quoteInto("role_id = ?", Tools_Security_Acl::ROLE_ADMIN);
                $admins = $userMapper->fetchAll($where);
                if(!empty($admins)){
                    foreach($admins as $key=>$admin){
                        if($key == 0){
                            $this->_mailer->setMailTo($admin->getEmail());
                        }else{
                            array_push($adminBccArray, $admin->getEmail());
                        }
                    }
                    if(!empty($adminBccArray)){
                        $this->_mailer->setMailBcc($adminBccArray);
                    }
                    }else{
                        return;
                    }
                break;
            case self::RECIPIENT_SUPERADMIN:
                $superAdmin = Application_Model_Mappers_UserMapper::getInstance()->findByRole(Tools_Security_Acl::ROLE_SUPERADMIN);
                $this->_mailer->setMailTo($superAdmin->getEmail());
                break;
        }
        return $this->_mailer->send();
    }


    protected function _sendTsystemnotificationMail() {

    }

    protected function _sendTuserinvitationMail(Application_Model_Models_User $user) {

        $fullName = $user->getFullName();
        if (empty($fullName)) {
            $fullName = '';
        }

        $resetTokenModel = $this->_options['resetToken'];
        $wicEmail = $this->_configHelper->getConfig('wicEmail');

        $emailContent = $this->_prepareEmailBody();
        $this->_entityParser->objectToDictionary($user);
        $this->_entityParser->addToDictionary(
            array(
                'reset:link' => '<a href="' . $resetTokenModel->getResetUrl() . '">' . $resetTokenModel->getResetUrl() . '</a>',
                'reset:url'  => $resetTokenModel->getResetUrl(),
                'widcard:BizEmail' => !empty($wicEmail) ? $wicEmail : $this->_configHelper->getConfig('adminEmail')
            )
        );
        $this->_mailer->setBody($this->_entityParser->parse($emailContent));
        $this->_mailer->setMailTo($user->getEmail())->setMailToLabel($fullName);
        $this->_mailer->setMailFrom($this->_entityParser->parse($this->_options['from']));
        $subject = ($this->_options['subject'] == '') ? $this->_translator->translate('invitation'):$this->_options['subject'];
        $this->_mailer->setMailFromLabel($subject);
        $this->_mailer->setSubject($subject);

        return $this->_mailer->send();
    }

    protected function _prepareEmailBody($pageId = 0) {
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
                'themePath'    => Tools_Filesystem_Tools::cleanWinPath($themeData['path']),
            );

            $cDbTable = new Application_Model_DbTable_Container();
            $select = $cDbTable->getAdapter()->select()->from('container', array(
                'uniqHash' => new Zend_Db_Expr("MD5(CONCAT_WS('-',`name`, COALESCE(`page_id`, 0), `container_type`))"),
                'id',
                'name',
                'page_id',
                'container_type',
                'content',
                'published',
                'publishing_date'
            ))
            ->where('(container_type = 2 OR container_type = 4)')
            ->where('page_id IS NULL');
            $stat   = $cDbTable->getAdapter()->fetchAssoc($select);
            if ($pageId) {
                $parserOptions['id'] = $pageId;
            }
            $parser = new Tools_Content_Parser($mailTemplate, array('containers' => $stat), $parserOptions);

            return Tools_Content_Tools::stripEditLinks($parser->parseSimple());
        }
        return false;
    }

    private function _cleanFormData($data) {
        unset($data['controller']);
        unset($data['action']);
        unset($data['module']);
        unset($data['formName']);
        unset($data['captcha']);
        unset($data['captchaId']);
        return $data;
    }
    
    private function _prepareEmail($emails){
        if(preg_match('~,~', $emails)){
            $mailArray = array();
            $contanctEmails = explode(',',$emails);
            foreach($contanctEmails as $email){
               $email = str_replace(" ",'',$email);
               array_push($mailArray, $email);
            }
            return $mailArray;
        }
        return array($emails);

    }

    /**
     * Adding additional lexemes
     *
     * @param array $params form params
     * @param string $lexemePrefix lexeme prefix
     */
    private function _addToDictionaryLexemes(array $params, $lexemePrefix = 'form')
    {
        $paramsDictionary = array();
        foreach ($params as $paramName => $paramValue) {
            $paramsDictionary[$lexemePrefix . ':' . $paramName] = $paramValue;
        }

        $this->_entityParser->addToDictionary($paramsDictionary);
    }

    /**
     * Prepare form fields info in html format
     *
     * @param array $formDetails form data
     * @return string
     */
    private function _prepareFormDetailsHtml(array $formDetails)
    {
        $formDetailsHtml = '';
        foreach ($formDetails as $name => $value) {
            if (!$value) {
                continue;
            }
            $formDetailsHtml .= '<b>' . str_replace(array('_', '-'), ' ', ucfirst($name)) . '</b>' . ': ' . (is_array($value) ? implode(', ', $value) : $value) . '<br />';
        }

        return $formDetailsHtml;
    }

}
