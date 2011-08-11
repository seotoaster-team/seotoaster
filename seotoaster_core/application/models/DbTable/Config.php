<?php

class Application_Model_DbTable_Config extends Zend_Db_Table_Abstract {

	protected $_name	= 'config';

	public function selectConfig() {
		return $this->getAdapter()->fetchPairs($this->select()->from($this->_name));
	}
	
	public function updateConfigParam($name, $value) {	
		
		$rowset = $this->find($name);
		$row = $rowset->current();
		if ($row === null) {
			$row = $this->createRow( array(
				'name'	=> $name,
				'value' => $value
			));			
		} else {
			$row->value = $value;
		}
		
		return $row->save();
	}
	
	
}

