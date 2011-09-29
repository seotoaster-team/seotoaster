<?php

/**
 * Seo garbage collector
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */

class Tools_Seo_GarbageCollector extends Tools_System_GarbageCollector {

	protected function _runOnDefault() {

	}

	protected function _runOnDelete() {
		if($this->_object instanceof Application_Model_Models_Silo) {
			$this->_cleanSiloPages();
		}
	}


	/**
	 * $this->_object represents Application_Model_Models_Silo
	 */
	private function _cleanSiloPages() {
		$siloPages = $this->_object->getRelatedPages();
		if(!empty ($siloPages)) {
			array_map(function($page) {
				$page->setSiloId(0);
				Application_Model_Mappers_PageMapper::getInstance()->save($page);
			}, $siloPages);
		}
	}
}

