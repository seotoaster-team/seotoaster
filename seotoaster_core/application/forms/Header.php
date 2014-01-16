<?php

class Application_Form_Header extends Application_Form_Container {

	public function init() {
		if(!$this->_containerType) {
			$this->_containerType = Application_Model_Models_Container::TYPE_REGULARHEADER;
		}

		$this->addAttribs(array(
			'class' => '_fajax _reload'
		));

		$this->addElement('text', 'content', array(
			'id'       => 'content',
			'value'    => $this->_content,
			'filters'  => array('StringTrim'),
			'class'    => 'header-content'
		));

		parent::init();

		$this->addElement('submit', 'submit', array(
			'id'     => 'btn-submit',
			'label'  => 'Save content',
			'class'  => 'formsubmit mt15px',
			'ignore' => true
		));
    }
}