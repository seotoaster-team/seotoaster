<?php

class Application_Form_Template extends Zend_Form {

	protected $_title        = '';

	protected $_content      = '';

	protected $_previewImage = '';

	protected $_templateId   = '';

	protected $_themeName    = '';

	public function init() {
		$this->setMethod(Zend_Form::METHOD_POST);
		$this->setAttrib('id', 'frm_template');

		$this->addElement('text', 'name', array(
			'id'       => 'title',
			'label'    => 'Template name',
			'value'    => $this->_title,
			'required' => true,
			'filters'  => array('StringTrim')
		));

		$this->addElement('textarea', 'content', array(
			'id'       => 'content',
			'label'    => 'Paste your HTML code here',
			'cols'     => '85',
			'rows'     => '33',
			'value'    => $this->_content,
			'required' => true,
			'filters'  => array('StringTrim')
		));

		$this->addElement('hidden', 'previewImage', array(
			'value' => $this->_previewImage,
			'id'    => 'preview_image'
		));

		$this->addElement('hidden', 'id', array(
			'value' => $this->_templateId,
			'id'    => 'template_id'
		));

		$this->addElement('hidden', 'themeName', array(
			'value' => $this->_themeName,
			'id'    => 'theme_name'
		));

		$this->addElement('submit', 'submit', array(
			'label'  => 'Save template',
			'class'  => 'formsubmit',
			'ignore' => true
		));
	}

}

