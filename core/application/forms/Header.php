<?php

class Application_Form_Header extends Application_Form_Container {
	
	public function init() {
		if(!$this->_containerType) {
			$this->_containerType = Application_Model_Models_Container::TYPE_REGULARHEADER;
		}

		$this->addElement('text', 'content', array(
			'id'       => 'content',
			'value'    => $this->_content,
			'required' => true,
			'filters'  => array('StringTrim')
		));

		parent::init();
    }
}