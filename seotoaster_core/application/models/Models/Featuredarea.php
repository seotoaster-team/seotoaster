<?php

/**
 * Featuredarea
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Model_Models_Featuredarea extends Application_Model_Models_Abstract {

	protected $_name  = '';

	protected $_pages = array();

	private $_limit   = 0;

	private $_random  = false;

	public function addPage(Application_Model_Models_Page $page) {
		$this->_pages[] = $page;
	}

	public function deletePage(Application_Model_Models_Page $page) {
		unset($this->_pages[array_search($page, $this->_pages)]);
	}

	public function getPages() {
		if($this->_random) {
			shuffle($this->_pages);
		}
		return ($this->_limit) ? array_slice($this->_pages, 0, $this->_limit) : $this->_pages;
	}

	public function setPages($pages) {
		$this->_pages = $pages;
		return $this;
	}

	public function getName() {
		return $this->_name;
	}

	public function setName($name) {
		$this->_name = $name;
		return $this;
	}

	public function getLimit() {
		return $this->_limit;
	}

	public function setLimit($limit) {
		$this->_limit = $limit;
		return $this;
	}

	public function getRandom() {
		return $this->_random;
	}

	public function setRandom($random) {
		$this->_random = $random;
		return $this;
	}
}

