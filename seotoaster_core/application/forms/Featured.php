<?php

/**
 * Featured
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Form_Featured extends Zend_Form {

	protected $_name = '';


	public function init() {
		$this->setMethod(Zend_Form::METHOD_POST)
			 ->setAttrib('class', '_fajax')
			 ->setAttrib('data-callback', 'loadFaList');

		$this->addElement(new Zend_Form_Element_Text(array(
			'id'         => 'fa-name',
			'name'       => 'name',
			'label'      => 'Featured area name',
			'value'      => $this->_name,
			'validators' => array(
				new Zend_Validate_Db_NoRecordExists(array(
					'table' => 'featured_area',
					'field' => 'name'
				))
			),
			'required'   => true,
			'filters'    => array('StringTrim')
		)));

		$this->addElement(new Zend_Form_Element_Submit(array(
			'name'  => 'addFeaturedArea',
			'id'    => 'add-featured-area',
			'value' => 'Add featured area',
			'label' => 'Add featured area'
		)));

	}

}

