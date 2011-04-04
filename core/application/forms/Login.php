<?php

class Application_Form_Login extends Zend_Form {

    public function init() {

		$this->setMethod(Zend_Form::METHOD_POST);

		$this->addElement('text', 'email', array(
			'label'    => 'Email:',
			'required' => true,
			'filters'  => array('StringTrim')
		));

		$this->addElement('password', 'password', array(
			'label'    => 'Password:',
			'required' => true
		));

		$this->addElement('submit', 'submit', array(
			'label'  => 'Let me in',
			'ignore' => true,
			'id'     => 'submit'
		));

		$this->getElement('submit')->removeDecorator('DtDdWrapper');
		$this->getElement('submit')->removeDecorator('Label');
    }


}

