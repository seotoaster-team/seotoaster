<?php

/**
 * Signup form
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Form_Signup extends Application_Form_User {

	public function init() {
		parent::init();

        $this->addAttribs(array(
           'class' => 'seotoaster-signup',
           'id'    => 'seotoaster-signup-form'
        ));

		$this->removeElement('roleId');
		$saveButton = $this->getElement('saveUser');
		$this->removeElement('saveUser');
        $this->removeElement('gplusProfile');
        $this->removeElement(Tools_System_Tools::CSRF_SECURE_TOKEN);

		$this->addElement(new Zend_Form_Element_Captcha('verification', array(
			'label'   => "Please verify you're a human",
			'captcha' => array(
				'captcha'        => 'Image',
				'font'           => 'system/fonts/Alcohole.ttf',
				'imgDir'         => 'tmp',
				'imgUrl'         => 'tmp',
				'dotNoiseLevel'  => 0,
				'lineNoiseLevel' => 0,
				'wordLen'        => 5,
				'timeout'        => 300
			)
		)));

		$this->getElement('email')->addValidator(new Zend_Validate_Db_NoRecordExists(array(
			'table' => 'user',
		    'field' => 'email'
		)));

		$this->addElement(($saveButton->setLabel('Sign Up')));
		$this->_initDecorators();
	}

	protected function _initDecorators() {
		//setting up form element decorators
		$this->setDecorators(array(
			'FormElements',
			'Form'
		));
		$this->removeDecorator('HtmlTag');

		//setting up decorators for all form elements
		//changing html wrapper DtDd to p
		$this->setElementDecorators(array(
			'ViewHelper',
			'Errors',
			'Label',
			array('HtmlTag', array('tag' => 'p'))
		));
		//remove ViewHelper decorator from the captcha element
		$this->getElement('verification')->removeDecorator('ViewHelper');
		// remove Label decorator from submit button
		$this->getElement('saveUser')->removeDecorator('Label');
		$this->getElement('id')->removeDecorator('HtmlTag');
	}
}

