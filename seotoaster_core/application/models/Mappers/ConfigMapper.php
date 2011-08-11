<?php

/**
 * ConfigMapper
 *
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Application_Model_Mappers_ConfigMapper extends Application_Model_Mappers_Abstract {

	protected $_dbTable = 'Application_Model_DbTable_Config';
	
	public function save($config) {
		if (!is_array($config) || empty ($config)){
			throw new Exceptions_SeotoasterException('Given parameter should be non empty array');
		}
		
		array_walk($config, function($value, $key, $dbTable){
			$dbTable->updateConfigParam($key, $value);
		}, $this->getDbTable());
		
		return true;
	}
	
	public function getConfig() {
		return $this->getDbTable()->selectConfig(); //->toArray();
	}

}