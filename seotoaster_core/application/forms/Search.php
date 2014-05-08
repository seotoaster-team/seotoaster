<?php

/**
 * Search
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Form_Search extends Zend_Form {

    /**
     * @var string Search term
     */
    protected $_search        = '';

	public function init() {
        $this->setMethod(Zend_Form::METHOD_GET)
            ->setAttrib('id', 'search-form');

        $this->addElement(new Zend_Form_Element_Text(array(
            'id'       => 'search',
            'name'     => 'search',
            'label'    => '',
            'value'    => $this->_search,
            'required' => true,
            'filters'  => array(new Zend_Filter_StringTrim()),
            'validators' => array(
                new Zend_Validate_StringLength(array('min' => 3))
            )
        )));

		$this->_initDecorators();
	}

	protected function _initDecorators() {
		//setting up form element decorators
		$this->setDecorators(array(
			'FormElements',
			'Form'
		));
		$this->removeDecorator('HtmlTag');

		//setting up decorators for all form elements
		//changing html wrapper DtDd to p
		$this->setElementDecorators(array(
			'ViewHelper',
			'Errors',
			'Label',
			array('HtmlTag', array('tag' => 'p'))
		));
	}

    /**
     * Returns current search term
     * @return string
     */
    public function getSearch() {
		return $this->_search;
	}

    /**
     * Set search term
     * @param $search Search term
     * @return $this Instance of Application_Form_Search for chaining
     */
    public function setSearch($search) {
		$this->_search = $search;
		$this->getElement('search')->setValue($search);
		return $this;
	}

}

