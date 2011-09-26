<?php

/**
 * Silo
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Model_Models_Silo extends Application_Model_Models_Abstract {

	protected $_name = '';

	protected $_relatedPages = array();

	public function getRelatedPages() {
		return $this->_relatedPages;
	}

	public function setRelatedPages($relatedPages) {
		$this->_relatedPages = $relatedPages;
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

