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
			$siloId = $this->getDbTable()->insert($data);
			$this->_saveSiloPages($siloId, $silo->getRelatedPages());
			return $siloId;
		}
		else {
			$this->_saveSiloPages($silo->getId(), $silo->getRelatedPages());
			return $this->getDbTable()->update($data, array('id = ?' => $id));
		}
	}

	public function find($id, $loadPages = true) {
		$result    = $this->getDbTable()->find($id);
		if(0 == count($result)) {
			return null;
		}
		$row  = $result->current();
		$silo = new $this->_model($row->toArray());

		if($loadPages) {
			$silo->setRelatedPages($this->_findSiloPages($row));
		}
		return $silo;
	}

	public function findByName($siloName, $loadPages = true) {
		$row = $this->getDbTable()->fetchAll($this->getDbTable()->getAdapter()->quoteInto("name=?", $siloName))->current();
		if($row === null) {
			return null;
		}
		$silo = new $this->_model($row->toArray());
		if($loadPages) {
			$silo->setRelatedPages($this->_findSiloPages($row));
		}
		return $silo;
	}

	private function _findSiloPages($siloRow) {
		$siloPages = array();
		$rowsPageSilo = $siloRow->findDependentRowset('Application_Model_DbTable_Page');
		foreach ($rowsPageSilo as $key => $rowPageSilo) {
			$siloPages[] = new Application_Model_Models_Page($rowPageSilo->toArray());
		}
		return $siloPages;
	}

	private function _saveSiloPages($siloId, $pages) {
		if(!empty ($pages)) {
			foreach($pages as $page) {
				$page->setSiloId($siloId);
				Application_Model_Mappers_PageMapper::getInstance()->save($page);
			}
		}
	}

	public function delete(Application_Model_Models_Silo $silo) {
		$where = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $silo->getId());
		$deleteResult = $this->getDbTable()->delete($where);
		$silo->notifyObservers();
		return $deleteResult;
	}
}

