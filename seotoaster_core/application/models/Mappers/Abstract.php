<?php

abstract class Application_Model_Mappers_Abstract {

	protected $_dbTable        = null;

	protected $_model          = '';

	protected static $_instances = null;

	private function __construct() {}

	private function __clone() {}

	public static function getInstance() {
		$class = get_called_class();
		if(!isset(self::$_instances[$class])) {
			self::$_instances[$class] = new $class();
		}
		return self::$_instances[$class];
	}

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

	public function find($id) {
		$result = $this->getDbTable()->find($id);
		if(0 == count($result)) {
			return null;
		}
		$row = $result->current();
		return new $this->_model($row->toArray());
	}

	protected function _findWhere($where) {
		$row = $this->getDbTable()->fetchAll($where)->current();
		if(null == $row) {
			return null;
		}
		return new $this->_model($row->toArray());
	}

	public function fetchAll($where = null, $order = array()) {
		$entries = array();
		$resultSet = $this->getDbTable()->fetchAll($where, $order);
		if(null === $resultSet) {
			return null;
		}
		foreach ($resultSet as $row) {
			$entries[] = new $this->_model($row->toArray());
		}
		return $entries;
	}
}

