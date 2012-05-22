<?php

/**
 * Featured widget garbage collector
 */
class Tools_Featured_GarbageCollector extends Tools_System_GarbageCollector {

	protected function _runOnDefault() {

	}

	protected function _runOnCreate() {
	}

	protected function _runOnUpdate() {
		$this->_cleanCachedFeaturedData();
	}


	protected function _runOnDelete() {
		$this->_cleanCachedFeaturedData();
	}

	private function _cleanCachedFeaturedData(){
		$cacheHelper   = Zend_Controller_Action_HelperBroker::getStaticHelper('cache');
		$cacheHelper->clean(false, false, array('fa_'.$this->_object->getName()));
	}
}

