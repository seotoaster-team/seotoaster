<?php

/**
 * Core
 *
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Installer_Form_Core extends Zend_Form {

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
		
		$this->addElement('text', 'corepath', array(
			'value'		=> $this->_corepath,
			'label'		=> 'Path to core',
			'class'		=> 'livecheck'
		));
		
		$this->addElement('text', 'sitename', array(
			'value'		=> $this->_sitename,
			'label'		=> 'Site name',
			'class'		=> 'livecheck',
			'validators'=> array(
				new Zend_Validate_Hostname(array(
					'allow' => Zend_Validate_Hostname::ALLOW_DNS | Zend_Validate_Hostname::ALLOW_IP | Zend_Validate_Hostname::ALLOW_LOCAL,
					'idn'   => false,
					'tld'   => false
					))
				)
		));
		$this->addElement('hidden', 'check', array(
			'value'	=> 'core',
			'ignore'=> true
		));
//		$this->addElement('submit', 'submit', array(
//			'label'		=> 'Check',
//			'decorators'=> array(
//				'ViewHelper',
//				new Zend_Form_Decorator_HtmlTag(array('tag' => 'div', 'class' => array('grid_12','mt5px') ))
//				)
//		));
		
		$this->setElementFilters(array(new Zend_Filter_StringTrim(), new Zend_Filter_StripTags()));
	}
	
	public function processErrors(){
		foreach ($this->getMessages() as $name => $errors){
			$this->getElement($name)->setAttrib('class', 'error');
		}
	}
	
}