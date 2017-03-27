<?php

class Application_Form_Login extends Zend_Form {

    public function init() {
		parent::init();

		$this->setMethod(Zend_Form::METHOD_POST);

	    $this->setAttribs(array(
		    'id' => 'login-form'
	    ));

		$this->addElement('text', 'email', array(
			'label'      => 'E-mail',
			'required'   => true,
			'filters'    => array('StringTrim'),
			'validators' => array(new Zend_Validate_EmailAddress())
		));

		$this->addElement('password', 'password', array(
			'label'    => 'Password',
			'required' => true
		));

		$this->addElement('hidden', 'secureToken', array(
			'required' => true
		));

		$this->addElement('submit', 'submit', array(
			'label'  => 'Let me in',
			'ignore' => true,
			'id'     => 'submit',
			'class'  => 'btn'
		));

		$this->getElement('submit')->removeDecorator('DtDdWrapper');
		$this->getElement('submit')->removeDecorator('Label');
	    $this->removeDecorator('DtDdWrapper');
	    $this->removeDecorator('DlWrapper');
    }


}