<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Code
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Form_Code extends Application_Form_Container {

	public function init() {

		if(!$this->_containerType) {
			$this->_containerType = Application_Model_Models_Container::TYPE_REGULARCONTENT;
		}

		$this->addElement('textarea', 'content', array(
			'id'       => 'content',
			'cols'     => '85',
			'rows'     => '30',
			'class'    => 'code-content',
			'style'    => 'height: 220px; font-family: Arial monospace; width: 458px;',
			'value'    => $this->_content,
			'filters'  => array('StringTrim')
		));

		$this->addAttribs(array(
			'class' => '_fajax _reload'
		));

		parent::init();

		$this->removeDecorator('DtDdWrapper');
		$this->removeDecorator('DlWrapper');

		$this->getElement('submit')->setDecorators(array(
			'ViewHelper',
            'Errors',
			array(
				array('data' => 'HtmlTag'),
				array('tag' => 'div')
			)
		));
	}

}

