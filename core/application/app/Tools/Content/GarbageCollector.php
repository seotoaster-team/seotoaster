<?php

/**
 * Description of GarbageCollector
 *
 * @author Seotoaster Dev Team
 */
class Tools_Content_GarbageCollector implements Interfaces_Observer, Interfaces_GarbageCollector {

	public function clean() {
		$this->_collectContainers();
	}

	private function _collectContainers() {
		// 1. Get containers from database
		// 2. Scan new theme for containers
		// 3. Compare and find unused containers
	}

	public function notify($object) {
		if($object instanceof Application_Model_Models_Container) {
			//Zend_Debug::dump('Content updated');
		}
	}



}

