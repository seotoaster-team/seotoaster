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
			'filters'  => array('StringTrim')
		)));

		$this->addElement(new Zend_Form_Element_Submit(array(
			'name'  => 'addSilo',
			'id'    => 'add-silo',
			'value' => 'Add silo',
			'class' => 'grid_3 omega',
			'label' => 'Add silo'
		)));

		$this->setElementDecorators(array('ViewHelper', 'Label'));

		$this->getElement('addSilo')->setDecorators(array('ViewHelper'));

	}

}

