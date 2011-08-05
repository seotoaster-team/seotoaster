<?php

/**
 * Featuredarea
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Model_Models_Featuredarea extends Application_Model_Models_Abstract {

	protected $_name  = '';

	protected $_pages = array();

	public function addPage(Application_Model_Models_Page $page) {
		$this->_pages[] = $page;
	}

	public function deletePage(Application_Model_Models_Page $page) {
		unset($this->_pages[array_search($page, $this->_pages)]);
	}

	public function getPages() {
		return $this->_pages;
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
}

