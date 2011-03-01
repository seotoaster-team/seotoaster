<?php

class Application_Model_DbTable_Config extends Zend_Db_Table_Abstract {

	protected $_name = 'config';

	public function selectConfig() {
		return $this->getAdapter()->fetchPairs($this->select()->from($this->_name));
	}
}

