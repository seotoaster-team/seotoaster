<?php

/**
 * NewsCategory
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Model_Models_NewsCategory extends Application_Model_Models_Abstract {

	protected $_name      = '';

	protected $_newsItems = array();

	public function getName() {
		return $this->_name;
	}

	public function setName($name) {
		$this->_name = $name;
		return $this;
	}

	public function getNewsItems() {
		return $this->_newsItems;
	}

	public function setNewsItems($newsItems) {
		$this->_newsItems = $newsItems;
		return $this;
	}


}

