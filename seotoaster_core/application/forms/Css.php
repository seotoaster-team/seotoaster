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
			'id'		=> 'csslist',
			'class'		=> 'w70'
		));
        $this->getElement('cssname')->setDisableTranslator(true);

		$this->addElement('textarea', 'content', array(
			'id'		 => 'csscontent',
			'required'	 => true,
			'allowEmpty' => true,
			'spellcheck' => 'false',
			'value'		 => $this->_content
		));

		$this->addElement(new Zend_Form_Element_Button(array(
			'type'   => 'submit',
			'name'   => 'submit',
			'label'  => 'Save CSS',
			'class'  => 'btn ticon-save formsubmit',
			'ignore' => true,
			'escape' => false
		)));

		$this->setDecorators(array('ViewScript'))
			->setElementDecorators(array('ViewHelper'))
			->setElementFilters(array('StringTrim'));
	}
}