<?php

/**
 * Setting
 *
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Installer_Form_Settings extends Zend_Form {

	public function init() {
		$this->setName(strtolower(__CLASS__))
			 ->setAction('')
			 ->setMethod(Zend_Form::METHOD_POST)
			 ->setDecorators(array(
				'FormElements',
				'Form'
				))
			 ->setElementDecorators(array(
				'ViewHelper',
				'Label',
				new Zend_Form_Decorator_HtmlTag(array('tag' => 'div', 'class' => array('grid_12','mt5px') ))
				));
			
		$this->addElement('text', 'adminEmail', array(
			'value'		=> '',
			'label'		=> 'Email',
			'title'		=> 'We will not use it for spamming',
			'required'	=> true,
			'allowEmpty'=> false,
			'validators'=> array(new Zend_Validate_EmailAddress())
		));
		
		$this->addElement('password', 'adminPassword', array(
			'value'		=> '',
			'label'		=> 'Password',
			'title'		=> 'At least 5 characters',
			'renderPassword' => true,
			'required'	=> true,
			'allowEmpty'=> false,
			'validators'=> array(new Zend_Validate_StringLength(array('min'=>5))),
		));
		
		$this->addElement('hidden', 'check', array(
			'value'	=> 'settings',
			'ignore'=> true
		));
		
		$this->addElement('submit', 'submit', array(
			'label'		=> 'Hit me!',
			'decorators'=> array(
				'ViewHelper',
				new Zend_Form_Decorator_HtmlTag(array('tag' => 'div', 'class' => array('grid_12','mt5px') ))
				)
		));
		
		$this->setElementFilters(array(new Zend_Filter_StringTrim(), new Zend_Filter_StripTags()));
	}
	
	public function processErrors(){
		foreach ($this->getMessages() as $name => $errors){
			$this->getElement($name)->setAttrib('class', 'error');
		}
	}
	
}