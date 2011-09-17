<?php

/**
 * SiloMapper
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Model_Mappers_SiloMapper extends Application_Model_Mappers_Abstract {

	protected $_dbTable = 'Application_Model_DbTable_Silo';

	protected $_model   = 'Application_Model_Models_Silo';

	public function save($silo) {
		if(!$silo instanceof Application_Model_Models_Silo) {
			throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_Models_Silo instance');
		}
		$data = array(
			'name'    => $silo->getName()
		);
		if(null === ($id = $silo->getId())) {
			unset($data['id']);
			return $this->getDbTable()->insert($data);
		}
		else {
			return $this->getDbTable()->update($data, array('id = ?' => $id));
		}
	}
}

