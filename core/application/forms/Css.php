<?php
/**
 * Css
 *
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Application_Form_Css extends Zend_Form {
	protected $_content = '';
	protected $_cssList = '';
    
	public function init() {
		$this->setMethod(Zend_Form::METHOD_POST);
		$this->setAttrib('id', 'editcssform');

		$this->addElement('select', 'cssname', array(
			'required'	=> 'false',
			'id'		=> 'csslist'
		));

		$this->addElement('textarea', 'content', array(
			'id'		=> 'content',
			'required'	=> true,
			'value'		=> $this->_content,
			'class'		=> array('h400'),
			'style'		=> 'font-family: monospace;font-weight:normal;'
		));

		$this->addElement('submit', 'submit', array(
			'class'		=> array('formsubmit', 'w200'),
			'ignore'	=> true,
			'label'		=> 'Save CSS'
		));
		
		$this->setDecorators(array('ViewScript'))
			->setElementDecorators(array('ViewHelper'))
			->setElementFilters(array('StringTrim'));
	}
}