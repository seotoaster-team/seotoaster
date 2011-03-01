<?php

abstract class Application_Model_Mappers_Abstract {

	protected $_dbTable = null;

	public function setDbTable($dbTable) {
		if(is_string($dbTable)) {
			$dbTable = new $dbTable();
		}
		if(!$dbTable instanceof Zend_Db_Table_Abstract) {
			throw new Exception('Invalid table data gateway provided');
		}
		$this->_dbTable = $dbTable;
		return $this;
	}

	public function getDbTable() {
		if(is_string($this->_dbTable)) {
			$this->setDbTable($this->_dbTable);
		}
		if(null === $this->_dbTable) {
			throw new Exception('Invalid table name provided');
		}
		return $this->_dbTable;
	}

	abstract public function save($model);

    abstract public function find($id);

	abstract public function fetchAll();

}

