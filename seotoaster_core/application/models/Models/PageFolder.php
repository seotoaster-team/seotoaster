<?php

class Application_Model_Models_PageFolder extends Application_Model_Models_Abstract {

	protected $_name;

	protected $_indexPage;

	public function getName() {
		return $this->_name;
	}

	public function setName($name) {
		$this->_name = $name;
		return $this;
	}

	public function getIndexPage() {
		return $this->_indexPage;
	}

	public function setIndexPage($indexPage) {
		$this->_indexPage = $indexPage;
		return $this;
	}
}