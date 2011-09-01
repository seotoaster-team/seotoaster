<?php

/**
 * Database
 *
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Installer_Form_Database extends Zend_Form {

	public function init(){
		
//		$this->setName(strtolower(__CLASS__))
//			 ->setAction('')
//			 ->setAttrib('class', 'ui-helper-clearfix')
//			 ->setMethod(Zend_Form::METHOD_POST)
//			 ->setDecorators(array(
//				'FormElements',
//				'Form'
//				))
//			 ->setElementDecorators(array(
//				'ViewHelper',
//				'Label',
//				new Zend_Form_Decorator_HtmlTag(array('tag' => 'div', 'class' => array('grid_12','mt5px') ))
//				));
		
		$this->addElement('text', 'host', array(
			'value'		=> 'localhost',
			'label'		=> 'Host',
			'validators'=> array(
				new Zend_Validate_Hostname(array(
					'allow' => Zend_Validate_Hostname::ALLOW_DNS | Zend_Validate_Hostname::ALLOW_IP | Zend_Validate_Hostname::ALLOW_LOCAL,
					'idn'   => false,
					'tld'   => false
					))
				)
		));
		
		$this->addElement('text', 'username', array(
			'label'		=> 'User'
		));
		
		$this->addElement('password', 'password', array(
			'label'		=> 'Password',
			'renderPassword' => true
		));
		
		$this->addElement('text', 'dbname', array(
			'label'		=> 'Database name'
		));
		
		$this->addElement('hidden', 'check', array(
			'value'	=> 'db',
			'ignore'=> true
		));
		
		$this->addElement('submit', 'submit', array(
			'label'		=> 'Go ahead!',
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