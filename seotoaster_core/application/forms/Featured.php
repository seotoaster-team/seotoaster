<?php

/**
 * Featured
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Form_Featured extends Application_Form_Secure {

	protected $_name = '';


	public function init() {
        parent::init();
        $this->setMethod(Zend_Form::METHOD_POST)
			 ->setAttrib('class', '_fajax')
			 ->setAttrib('data-callback', 'loadFaList');

		$this->addElement(new Zend_Form_Element_Text(array(
			'id'         => 'fa-name',
			'name'       => 'name',
			'label'      => 'Page tag name',
			'value'      => $this->_name,
			'validators' => array(
				new Zend_Validate_Db_NoRecordExists(array(
					'table' => 'featured_area',
					'field' => 'name'
				))
			),
			'decorators' => array('ViewHelper', 'Label'),
			'required'   => true,
			'filters'    => array('StringTrim')
		)));

		$this->addElement(new Zend_Form_Element_Button(array(
			'name'  => 'addFeaturedArea',
			'id'    => 'add-featured-area',
			'value' => 'Add page tag',
			'type'  => 'submit',
			'class' => 'btn block transparent mt10px ticon-plus',
			'escape'=> false
		)));

		$this->getElement('addFeaturedArea')->removeDecorator('DtDdWrapper');

	}

}

