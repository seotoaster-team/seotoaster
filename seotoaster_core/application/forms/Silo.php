<?php

/**
 * Sculpting
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Form_Silo extends Zend_Form {

	protected $_name = '';

	public function init() {
		$this->setMethod(Zend_Form::METHOD_POST);

		$this->addElement(new Zend_Form_Element_Text(array(
			'id'       => 'silo-name',
			'name'     => 'name',
			'label'    => 'Silo name',
			'value'    => $this->_name,
			'required' => true,
            'class' => 'grid_9 alpha omega',
			'filters'  => array('StringTrim')
		)));

		$this->addElement(new Zend_Form_Element_Button(array(
			'name'  => 'addSilo',
			'id'    => 'add-silo',
			'value' => 'Add silo',
			'class' => 'btn ticon-plus grid_3 alpha omega mt0px',
			'label' => 'Add silo',
            'type'  => 'submit'
		)));

		$this->setElementDecorators(array('ViewHelper', 'Label'));

		$this->getElement('addSilo')->setDecorators(array('ViewHelper'));

	}

}

