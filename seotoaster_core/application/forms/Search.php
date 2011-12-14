<?php

/**
 * Search
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Form_Search extends Zend_Form {

	protected $_resultsPageId = 0;

	protected $_search       = '';

	public function init() {
		$this->setMethod(Zend_Form::METHOD_POST);
		$this->setAttribs(array(
			'id'     => 'search-form',
		));

		$this->addElement(new Zend_Form_Element_Text(array(
			'id'       => 'search',
			'name'     => 'search',
			'label'    => '',
			'value'    => $this->_search,
			'required' => true,
			'filters'  => array('StringTrim')
		)));

		$this->addElement(new Zend_Form_Element_Hidden(array(
			'id'       => 'results-page-id',
			'name'     => 'resultsPageId',
			'value'    => $this->_resultsPageId
		)));

		$this->addElement(new Zend_Form_Element_Submit(array(
			'name'  => 'doSearch',
			'id'    => 'do-search',
			'value' => 'doSearch',
			'label' => 'Search'
		)));
	}

	public function getResultsPageId() {
		return $this->_resultsPageId;
	}

	public function setResultsPageId($resultsPageId) {
		$this->_resultsPageId = $resultsPageId;
		$this->getElement('resultsPageId')->setValue($resultsPageId);
		return $this;
	}

	public function getSearch() {
		return $this->_search;
	}

	public function setSearch($search) {
		$this->_search = $search;
		$this->getElement('search')->setValue($search);
		return $this;
	}

}

