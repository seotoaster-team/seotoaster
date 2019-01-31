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
			'validators' => array(new Tools_System_CustomEmailValidator())
		));

		$this->addElement('password', 'password', array(
			'label'    => 'Password',
			'required' => true
		));

		$this->addElement('hidden', 'secureToken', array(
			'required' => true
		));

        $this->addElement(new Zend_Form_Element_Button(array(
            'name'   => 'submit',
            'ignore' => true,
            'label'  => 'Let me in',
            'type'   => 'submit'
        )));

	    $this->removeDecorator('DtDdWrapper');
	    $this->removeDecorator('DlWrapper');
    }


}