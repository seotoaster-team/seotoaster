<?php

class Application_Form_Template extends Zend_Form {

	protected $_title        = '';

	protected $_content      = '';

	protected $_previewImage = '';

	protected $_templateId   = '';

	protected $_themeName    = '';

	protected $_type        = '';

	public function init() {
		$this->setMethod(Zend_Form::METHOD_POST)
			 ->setAttrib('id', 'frm_template')
			 ->setDecorators(array('ViewScript'))
			 ->setElementDecorators(array('ViewHelper'));

		$this->addElement('text', 'name', array(
			'id'       => 'title',
			'label'    => 'Template name',
			'value'    => $this->_title,
			'required' => true,
			'filters'  => array('StringTrim'),
			'class'	   => array('templatename'),
			'decorators' => array('ViewHelper', 'Label'),
			'validators' => array(array('stringLength', false, array(3, 45)), new Zend_Validate_Alnum(true))
		));

		$this->addElement('textarea', 'content', array(
			'id'       => 'template-content',
			'label'    => 'Paste your HTML code here:',
			'cols'     => '85',
			'rows'     => '33',
			'value'    => $this->_content,
			'required' => true,
			'filters'  => array('StringTrim'),
			'class'	   => array('h440'),
			'decorators' => array('ViewHelper', 'Label')
		));

		$this->addElement(new Zend_Form_Element_Select(array(
			'name'         => 'templateType',
			'id'           => 'template-type',
			'label'        => 'Used for',
			'multiOptions' => array(
				Application_Model_Models_Template::TYPE_REGULAR => 'Regular page',
				Application_Model_Models_Template::TYPE_PRODUCT => 'Product page',
				Application_Model_Models_Template::TYPE_LISTING => 'Product listing',
				Application_Model_Models_Template::TYPE_MAIL    => 'E-mail sending'
			),
			'value'        => $this->_type
		)));

		$this->addElement('hidden', 'id', array(
			'value' => $this->_templateId,
			'id'    => 'template_id'
		));

		$this->addElement(new Zend_Form_Element_Hidden(array(
			'id'    => 'pageId',
			'name'  => 'pageId',
		)));

		$this->addElement('submit', 'submit', array(
			'label'  => 'Save changes',
			'class'  => array('formsubmit', 'w250'),
			'ignore' => true
		));
	}

}

