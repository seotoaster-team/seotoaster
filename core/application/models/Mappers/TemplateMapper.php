<?php

class Application_Model_Mappers_TemplateMapper extends Application_Model_Mappers_Abstract {

	protected $_dbTable = 'Application_Model_DbTable_Template';

	public function find($id) {
		$result = $this->getDbTable()->find($id);
		if(0 == count($result)) {
			return null;
		}
		$row = $result->current();
		return new Application_Model_Models_Template($row->toArray());
	}

    public function fetchAll() {
		$resultSet = $this->getDbTable()->fetchAll();
		Zend_Debug::dump($resultSet);
	}

	public function save($template) {
		
	}

}

