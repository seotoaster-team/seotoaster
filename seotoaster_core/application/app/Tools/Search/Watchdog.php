<?php

/**
 * Watchdog
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Search_Watchdog implements Interfaces_Observer {

	private $_options = array();

	private $_object  = null;

	public function __construct($options = array()) {
		$this->_options = $options;
	}

	public function notify($object) {
		$this->_object = $object;
		if($this->_object instanceof Application_Model_Models_Page) {
			$this->_onPageUpdateChain();
		}
		if($this->_object instanceof Application_Model_Models_Container) {
			$this->_onContainerUpdateChain();
		}
	}


	private function _onPageUpdateChain() {
		// add / update page in the search index
		Tools_Search_Tools::removeFromIndex($this->_object->getId());
		Tools_Search_Tools::addPageToIndex($this->_object);
	}

	private function _onContainerUpdateChain() {
		$page = Application_Model_Mappers_PageMapper::getInstance()->find($this->_object->getPageId());
		if($page !== null) {
			Tools_Search_Tools::removeFromIndex($page->getId());
			Tools_Search_Tools::addPageToIndex($page);
		}
	}
}

