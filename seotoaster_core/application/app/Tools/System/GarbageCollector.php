<?php

/**
 * GarbageCollector
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
abstract class Tools_System_GarbageCollector implements Interfaces_GarbageCollector, Interfaces_Observer {

	const CLEAN_ONDELETE = 'ondelete';

	const CLEAN_ONUPDATE = 'onupdate';

	const CLEAN_ONCREATE = 'oncreate';

	protected $_params   = array();

	protected $_object   = null;

	public function __construct($params = array()) {
		$this->_params = $params;
	}

	abstract protected function _runOnDefault();

	public function notify($object) {
		$this->_object = $object;
		if(isset($this->_params['action'])) {
			switch ($this->_params['action']) {
				case self::CLEAN_ONCREATE:
					$this->_runOnCreate();
				break;
				case self::CLEAN_ONUPDATE:
					$this->_runOnUpdate();
				break;
				case self::CLEAN_ONDELETE:
					$this->_runOnDelete();
				break;
				default :
					$this->_runOnDefault();
				break;
			}
		}
	}

	public function getObject() {
		return $this->_object;
	}

	public function setObject($object) {
		$this->_object = $object;
		return $this;
	}


	public function clean() {}

	protected function _runOnCreate() {}
	protected function _runOnUpdate() {}
	protected function _runOnDelete() {}

}

