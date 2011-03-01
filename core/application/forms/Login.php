<?php

class Application_Form_Login extends Zend_Form {

    public function init() {

		$this->setMethod('post');

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
			'ignore' => true
		));
		
    }


}

