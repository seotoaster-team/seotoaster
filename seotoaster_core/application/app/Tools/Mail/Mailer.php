<?php

/**
 * Mailer
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Mail_Mailer {

	const MAIL_TYPE_SMTP        = 'smtp';

	const MAIL_TYPE_MAIL        = 'mail';

	const BODY_TYPE_HTML        = 'html';

	const BODY_TYPE_TEXT        = 'text';

	private $_mailer            = null;

	private $_body              = '';

	/**
	 * Dictionary for the EntityParser
	 *
	 * @see Tools_Content_EntityParser
	 * @var array
	 */
	private $_dictonary         = array();

	private $_mailTemplateName  = '';

	/**
	 * Receipients (could be multiple)
	 *
	 * To allow multiple receipients it should be an array with emails and labels
	 *
	 * @var mixed | string or array
	 */
	private $_mailTo            = '';

	private $_mailFrom          = '';

	private $_mailToLabel       = '';

	private $_mailFromLabel     = '';

	private $_subject           = '';

	private $_encoding          = 'UTF-8';

	private $_bodyType          = self::BODY_TYPE_HTML;

	private $_transport         = self::MAIL_TYPE_MAIL;

	private $_smtpConfig        = array(
		'auth'		=> 'login',
		'username'  => '',
		'password'  => '',
		'host'      => '127.0.0.1'
	);

	private $_mailCc            = '';

	private $_mailBcc           = '';

	public function __construct($options = array()) {
		if(!empty ($options)) {
			$this->setOptions($options);
		}
		$this->_mailer = new Zend_Mail($this->_encoding);
	}

	public function send() {
		$this->_prepare();
		return $this->_send();
	}

	public function prepare() {
		$this->_prepare();
		return $this;
	}

	private function _prepare() {
		// setting transport (sendmail or smtp)
		switch ($this->_transport) {
			case self::MAIL_TYPE_MAIL:
				Zend_Mail::setDefaultTransport(new Zend_Mail_Transport_Sendmail());
			break;
			case self::MAIL_TYPE_SMTP:
				Zend_Mail::setDefaultTransport(new Zend_Mail_Transport_Smtp($this->_smtpConfig['host'], $this->_smtpConfig));
			break;
		}

		// setting mail to field (support multiple mail to)
		//$this->_mailTo = explode(',', $this->_mailTo);
		if(is_array($this->_mailTo)) {
			foreach($this->_mailTo as $label => $email) {
			    $this->_mailer->addTo($email, $label);
			}
		}
		else {
			$this->_mailer->addTo($this->_mailTo, $this->_mailToLabel);
		}
        
        if(is_array($this->_mailBcc) && count($this->_mailBcc) > 0) {
			foreach($this->_mailBcc as $email) {
				$this->_mailer->addBcc($email);
			}
		}

		//setting from field and subject
		$this->_mailer->setFrom($this->_mailFrom, $this->_mailFromLabel)
			->setSubject($this->_subject);

		//setting correct body (html or text)
		if($this->_bodyType == self::BODY_TYPE_HTML) {
			$this->_mailer->setBodyHtml($this->_body);
		}
		else if($this->_bodyType == self::BODY_TYPE_TEXT) {
			$this->_mailer->setBodyText($this->_body);
		}
	}

	private function _send() {
		try {
			return $this->_mailer->send();
		}
		catch (Zend_Mail_Exception $zme) {
			error_log($zme->getMessage());
			error_log($zme->getTraceAsString());
			return false;
		}
	}

	public function getSmtpConfig() {
		return $this->_smtpConfig;
	}

	/**
	 * Should be an array with options for smtp connection configuration
	 *
	 * @param array $smtpConfig array('host' => '', 'username' => '', 'password' => '')
	 * @return Tools_Mail_Mailer
	 */
	public function setSmtpConfig(array $smtpConfig) {
		$this->_smtpConfig = array_merge($this->_smtpConfig, $smtpConfig);
		return $this;
	}

	public function getMailTemplateName() {
		return $this->_mailTemplateName;
	}

	/**
	 * Sets the name of the mail template to use
	 *
	 * If secont parameter set to true then instance of template object
	 * Will be created and mailer body will be filled with template content.
	 *
	 * @param string $bodyTemplateName
	 * @param boolean $initBody
	 * @return Tools_Mail_Mailer
	 */
	public function setMailTemplateName($bodyTemplateName, $initBody = false) {
		$this->_mailTemplateName = $bodyTemplateName;
		if($initBody) {
			$entityParser = new Tools_Content_EntityParser();
			$entityParser->setDictionary($this->_dictonary);
			$bodyTemplate = Application_Model_Mappers_TemplateMapper::getInstance()->find($this->_mailTemplateName);
			$this->_body  = $entityParser->parse($bodyTemplate->getContent());
		}
		return $this;
	}

	public function getBody() {
		return $this->_body;
	}

	public function setBody($body) {
		$this->_body = $body;
		return $this;
	}

	public function getMailTo() {
		return $this->_mailTo;
	}

	/**
	 * Set receipient(s)
	 *
	 * To allow multiple receipients pass an asosiative array with e-mails and labels
	 *
	 * @param mixed $mailTo
	 * @return Tools_Mail_Mailer
	 */
	public function setMailTo($mailTo) {
		$this->_mailTo = $mailTo;
		return $this;
	}

	public function getMailFrom() {
		return $this->_mailFrom;
	}

	public function setMailFrom($mailFrom) {
		$this->_mailFrom = $mailFrom;
		return $this;
	}

	public function getMailToLabel() {
		return $this->_mailToLabel;
	}

	public function setMailToLabel($mailToLabel) {
		$this->_mailToLabel = $mailToLabel;
		return $this;
	}

	public function getMailFromLabel() {
		return $this->_mailFromLabel;
	}

	public function setMailFromLabel($mailFromLabel) {
		$this->_mailFromLabel = $mailFromLabel;
		return $this;
	}

	public function getSubject() {
		return $this->_subject;
	}

	public function setSubject($subject) {
		$this->_subject = $subject;
		return $this;
	}

	public function getEncoding() {
		return $this->_encoding;
	}

	public function setEncoding($encoding) {
		$this->_encoding = $encoding;
		return $this;
	}

	public function getTransport() {
		return $this->_transport;
	}

	public function setTransport($transport) {
		$this->_transport = $transport;
		return $this;
	}

	public function getMailCc() {
		return $this->_mailCc;
	}

	public function setMailCc($mailCc) {
		$this->_mailCc = $mailCc;
		return $this;
	}

	public function getMailBcc() {
		return $this->_mailBcc;
	}

	public function setMailBcc($mailBcc) {
		$this->_mailBcc = $mailBcc;
		return $this;
	}

	public function setOptions() {
		//@TODO finish this method
	}

	public function addAttachment($attachment) {
        if(($attachment instanceof Zend_Mime_Part)){
            $this->_mailer->addAttachment($attachment);
        }elseif(is_array($attachment) && !empty($attachment)){
            foreach($attachment as $attach){
                if(($attach instanceof Zend_Mime_Part)){
                    $this->_mailer->addAttachment($attach);
                }
            }
        }
    }
    
    public function getBodyType() {
		return $this->_bodyType;
	}

	public function setBodyType($bodyType) {
		$this->_bodyType = $bodyType;
		return $this;
	}
}

