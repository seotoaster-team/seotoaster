<?php

class Application_Form_Form extends Zend_Form {

	protected $_code              = '';

	protected $_contactMail       = '';

	protected $_messageSuccess    = '';

	protected $_messageError      = '';

	protected $_replyFrom         = '';

	protected $_replySubject      = '';

	protected $_trackingCode      = '';

	protected $_replyMailTemplate = '';

    protected $_name              = '';


    public function init() {
		$this->setMethod(Zend_Form::METHOD_POST);

		$this->addElement(new Zend_Form_Element_Textarea(array(
			'id'       => 'code',
			'name'     => 'code',
			'label'    => 'Form code',
			'value'    => $this->_code,
            'cols'     => '45',
			'rows'     => '5',
			'required' => true,
			'filters'  => array('StringTrim')
		)));

        $this->addElement(new Zend_Form_Element_Text(array(
			'id'         => 'contact-mail',
			'name'       => 'contactMail',
			'label'      => 'Contact mail',
			'value'      => $this->_contactMail,
			'required'   => true,
			'filters'    => array('StringTrim'),
            'validators' => array(new Zend_Validate_EmailAddress())
		)));

        $this->addElement(new Zend_Form_Element_Text(array(
			'id'         => 'reply-from',
			'name'       => 'replyFrom',
			'label'      => 'Auto reply from',
			'value'      => $this->_replyFrom,
			'required'   => true,
			'filters'    => array('StringTrim'),
            'validators' => array(new Zend_Validate_EmailAddress())
		)));

		$this->addElement(new Zend_Form_Element_Select(array(
			'id'         => 'reply-mail-template',
			'name'       => 'replyMailTemplate',
			'label'      => 'Auto reply mail template',
			'value'      => $this->_replyMailTemplate,
			'required'   => true,
			'registerInArrayValidator' => false
		)));

        $this->addElement(new Zend_Form_Element_Textarea(array(
			'id'       => 'success-message',
			'name'     => 'messageSuccess',
			'label'    => 'Success Message:',
			'value'    => $this->_successMessage,
            'cols'     => '45',
			'rows'     => '1',
			'required' => true,
			'filters'  => array('StringTrim')
		)));

        $this->addElement(new Zend_Form_Element_Textarea(array(
			'id'       => 'error-message',
			'name'     => 'messageError',
			'label'    => 'Error Message:',
			'value'    => $this->_errorMessage,
            'cols'     => '45',
			'rows'     => '1',
			'required' => true,
			'filters'  => array('StringTrim')
		)));

        $this->addElement(new Zend_Form_Element_Text(array(
			'id'       => 'reply-subject',
			'name'     => 'replySubject',
			'label'    => 'Auto reply subject:',
			'value'    => $this->_replySubject,
			'required' => true,
			'filters'  => array('StringTrim'),

		)));

        $this->addElement(new Zend_Form_Element_Textarea(array(
			'id'       => 'tracking-code',
			'name'     => 'trackingCode',
			'label'    => 'Tracking conversion code',
			'value'    => $this->_trackingCode,
            'cols'     => '45',
			'rows'     => '5',
			'filters'  => array('StringTrim')
		)));

        $this->addElement(new Zend_Form_Element_Hidden(array(
			'id'    => 'form-name',
			'name'  => 'name',
			'value' => $this->_formName
		)));

        $this->addElement('submit', 'submit', array(
			'label' => 'Save'
		));

        $this->setElementDecorators(array('ViewHelper', 'Label'));
    }

    public function getCode() {
		return $this->_code;
	}

	public function setCode($code) {
		$this->_code = $code;
		$this->getElement('code')->setValue($code);
		return $this;
	}

    public function getEmailTo() {
		return $this->_emailTo;
	}

	public function setEmailTo($emailTo) {
		$this->_emailTo = $emailTo;
		$this->getElement('emailTo')->setValue($emailTo);
		return $this;
	}

    public function getMessageSuccess() {
		return $this->_messageSuccess;
	}

	public function setMessageSuccess($messageSuccess) {
		$this->_messageSuccess = $messageSuccess;
		$this->getElement('messageSuccess')->setValue($messageSuccess);
		return $this;
	}

    public function getMessageError() {
		return $this->_messageError;
	}

	public function setMessageError($messageError) {
		$this->_messageError = $messageError;
		$this->getElement('messageError')->setValue($messageError);
		return $this;
	}

    public function getEmailFrom() {
		return $this->_emailFrom;
	}

	public function setEmailFrom($emailFrom) {
		$this->_emailFrom = $emailFrom;
		$this->getElement('emailFrom')->setValue($emailFrom);
		return $this;
	}

    public function getEmailSubject() {
		return $this->_emailSubject;
	}

	public function setEmailSubject($emailSubject) {
		$this->_emailSubject = $emailSubject;
		$this->getElement('emailSubject')->setValue($emailSubject);
		return $this;
	}

    public function getTrackingCode() {
		return $this->_emailBody;
	}

	public function setTrackingCode($trackingCode) {
		$this->_trackingCode = $trackingCode;
		$this->getElement('trackingCode')->setValue($trackingCode);
		return $this;
	}

    public function getName() {
		return $this->_name;
	}

	public function setName($name) {
		$this->_name = $name;
		$this->getElement('name')->setValue($name);
		return $this;
	}

	public function getReplyTo() {
		return $this->_replyTo;
	}

	public function setReplyTo($replyTo) {
		$this->_replyTo = $replyTo;
		$this->getElement('replyTo')->setValue($replyTo);
		return $this;
	}

	public function getMailTemplate() {
		return $this->_mailTemplate;
	}

	public function setMailTemplate($mailTemplate) {
		$this->_mailTemplate = $mailTemplate;
		$this->getElement('mailTemplate')->setValue($mailTemplate);
		return $this;
	}

}