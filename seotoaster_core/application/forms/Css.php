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
			'required'	=> true,
			'id'		=> 'csslist'
		));

		$this->addElement('textarea', 'content', array(
			'id'		 => 'csscontent',
			'required'	 => true,
			'allowEmpty' => true,
			'spellcheck' => 'false',
			'value'		 => $this->_content,
			'class'		 => array('h420'),
			'style'		 => 'font-family: monospace;font-weight:normal;font-size:12px;'
		));

		$this->addElement('submit', 'submit', array(
			'class'		=> array('formsubmit'),
			'ignore'	=> true,
			'label'		=> 'Save CSS'
		));

		$this->setDecorators(array('ViewScript'))
			->setElementDecorators(array('ViewHelper'))
			->setElementFilters(array('StringTrim'));
	}
}