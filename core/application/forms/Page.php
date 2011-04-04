<?php

class Application_Form_Page extends Zend_Form {

	protected $_h1              = '';

	protected $_headerTitle     = '';

	protected $_url             = '';

	protected $_navName         = '';

	protected $_metaDescription = '';

	protected $_metaKeywords    = '';

	protected $_teaserText      = '';

	public function init() {
		$this->setMethod(Zend_Form::METHOD_POST);
		$this->setAttrib('id', 'frm_page');

		$this->addElement('text', 'h1', array(
			'id'       => 'h1',
			'label'    => 'Page header H1 tag',
			'value'    => $this->_h1,
			'required' => true,
			'filters'  => array('StringTrim')
		));

		$this->addElement('text', 'headerTitle', array(
			'id'       => 'header_title',
			'label'    => 'Display in browser title as',
			'value'    => $this->_headerTitle,
			'required' => true,
			'filters'  => array('StringTrim')
		));

		$this->addElement('text', 'url', array(
			'id'       => 'url',
			'label'    => 'Page URL in address bar',
			'value'    => $this->_url,
			'required' => true,
			'filters'  => array('StringTrim')
		));

		$this->addElement('text', 'navName', array(
			'id'       => 'nav_name',
			'label'    => 'Display in navigation as',
			'value'    => $this->_navName,
			'required' => true,
			'filters'  => array('StringTrim')
		));

		$this->addElement('textarea', 'metaDescription', array(
			'id'       => 'meta_description',
			'cols'     => '45',
			'rows'     => '7',
			'label'    => 'Meta description',
			'value'    => $this->_metaDescription,
			'required' => true,
			'filters'  => array('StringTrim')
		));

		$this->addElement('textarea', 'metaKeywords', array(
			'id'       => 'meta_keywords',
			'cols'     => '45',
			'rows'     => '3',
			'label'    => 'Meta keywords',
			'value'    => $this->_metaKeywords,
			'required' => true,
			'filters'  => array('StringTrim')
		));

		$this->addElement('textarea', 'teaserText', array(
			'id'       => 'teaser_text',
			'cols'     => '45',
			'rows'     => '3',
			'label'    => 'Teaser Text',
			'value'    => $this->_teaserText,
			'required' => true,
			'filters'  => array('StringTrim')
		));

		$this->addElement('radio', 'inMenu', array(
			'multiOptions' => array(
				Application_Model_Models_Page::IN_MAINMENU   => 'Main Menu',
				Application_Model_Models_Page::IN_STATICMENU => 'Static Menu',
				Application_Model_Models_Page::IN_NOMENU     => 'No Menu'
			)
		));

		$this->addElement('select', 'pageCategory', array(
			'id' => 'page_category'
		));
	}

}

