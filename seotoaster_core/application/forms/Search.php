<?php

/**
 * Search
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Form_Search extends Zend_Form {

//	protected $_resultsPageId = 0;

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
			'filters'  => array('StringTrim')
        )));

//		$this->addElement(new Zend_Form_Element_Hidden(array(
//			'id'       => 'results-page-id',
//			'name'     => 'resultsPageId',
//			'value'    => $this->_resultsPageId
//		)));

//		$this->addElement('submit', 'doSearch', array(
//			'name'  => null,
//            'id'    => 'do-search',
//            'value' => 'doSearch',
//            'label' => 'Search'
//		));

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
		// remove Label decorator from submit button
//		$this->getElement('doSearch')->removeDecorator('Label');
//		$this->getElement('resultsPageId')->removeDecorator('HtmlTag');
	}

//	public function getResultsPageId() {
//		return $this->_resultsPageId;
//	}
//
//	public function setResultsPageId($resultsPageId) {
//		$this->_resultsPageId = $resultsPageId;
//		$this->getElement('resultsPageId')->setValue($resultsPageId);
//		return $this;
//	}

	public function getSearch() {
		return $this->_search;
	}

	public function setSearch($search) {
		$this->_search = $search;
		$this->getElement('search')->setValue($search);
		return $this;
	}

}

