<?php
/**
 * User: iamne Eugene I. Nezhuta <eugene@seotoaster.com>
 * Date: 4/18/12
 * Time: 12:34 PM
 */

class Application_Form_PasswordReset extends Zend_Form {

	public function init() {

		$this->setAttribs(array(
			'id'     => 'password-reset',
			'method' => Zend_Form::METHOD_POST
		));

		$this->setDecorators(array('FormElements', 'Form'));

		$this->addElement(new Zend_Form_Element_Password(array(
			'name'     => 'password',
			'id'       => 'password',
			'label'    => 'Password',
			'errorMessages' => array(
				'isEmpty' => 'Invalid password'
			),
			'required' => true
		)));

		$this->addElement(new Zend_Form_Element_Password(array(
			'name'     => 'confirmPassword',
			'id'       => 'confirm-password',
			'label'    => 'Confirm password',
			'validators' => array(
				new Zend_Validate_Identical('password')
			),
			'required' => true
		)));

		$this->addDisplayGroups(array(
			'main' => array(
				'password',
				'confirmPassword'
			)
		));

		$main = $this->getDisplayGroup('main')
			->setDecorators(array(
				'FormElements',
		        'Fieldset',
		));

		$this->setElementDecorators(array(
			'ViewHelper',
			'Label',
			array('HtmlTag', array('tag' => 'div'))
		));

		$this->addElement(new Zend_Form_Element_Button(array(
			'name'   => 'reset',
			'ignore' => true,
			'label'  => 'Update',
            'type'   => 'submit'
		)));
	}

}
