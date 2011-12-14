<?php

class Application_Form_Content extends Application_Form_Container {

	public function init() {

		if(!$this->_containerType) {
			$this->_containerType = Application_Model_Models_Container::TYPE_REGULARCONTENT;
		}

		$this->addElement('textarea', 'content', array(
			'id'       => 'content',
			'cols'     => '85',
			'rows'     => '30',
			'class'    => 'tinymce',
			'value'    => $this->_content,
			'filters'  => array('StringTrim')
		));

		parent::init();

		$this->removeDecorator('DtDdWrapper');
		$this->removeDecorator('DlWrapper');
    }
}