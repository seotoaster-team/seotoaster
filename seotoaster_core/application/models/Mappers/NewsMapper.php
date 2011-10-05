<?php

/**
 * NewsMapper
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Model_Mappers_NewsMapper extends Application_Model_Mappers_PageMapper {

	protected $_model = 'Application_Model_Models_News';


	public function find($id) {
		$result = $this->getDbTable()->find($id);
		if(0 == count($result)) {
			return null;
		}
		$row     = $result->current();
		$newsRow = $this->_makeNewsRow($row);
		if($newsRow === null) {
			return $newsRow;
		}
		return new $this->_model($this->_makeNewsRow($row));
	}

	public function findByUrl($pageUrl) {
		$page = parent::findByUrl($pageUrl);
		if($page !== null && $page->getNews()) {
			return $page;
		}
		return null;
	}

	protected function _findWhere($where, $fetchSysPages = true) {
		$sysWhere  = $this->getDbTable()->getAdapter()->quoteInto("system = '?'", intval($fetchSysPages));
		$where    .= (($where) ? ' AND ' . $sysWhere : $sysWhere);
		$row = $this->getDbTable()->fetchAll($where)->current();
		if(null === $row) {
			return null;
		}
		return new $this->_model($this->_makeNewsRow($row));
	}

	public function fetchAll($where = '', $order = array(), $fetchSysPages = false) {
		$entries = array();
		$resultSet = $this->getDbTable()->fetchAll($where, $order);
		if(null === $resultSet) {
			return null;
		}
		foreach ($resultSet as $row) {
			$entries[] = new $this->_model($this->_makeNewsRow($row));
		}
		return $entries;
	}


	private function _makeNewsRow($row) {
		$categories   = array();
		$rowTemplate  = $row->findParentRow('Application_Model_DbTable_Template');
		$dependentRow = $row->findDependentRowset('Application_Model_DbTable_News')->current();
		if($dependentRow === null) {
			return null;
		}
		$categoriesRowSet = $dependentRow->findManyToManyRowset('Application_Model_DbTable_NewsCategory', 'Application_Model_DbTable_NewsRelCategory');
		if($categoriesRowSet === null) {
			$categories = array();
		}
		else {
			foreach ($categoriesRowSet as $categoryRow) {
				$categories[] = new Application_Model_Models_NewsCategory($categoryRow->toArray());
			}
		}

		$dependentRow = $dependentRow->toArray();
		if(!empty ($dependentRow)) {
			unset($dependentRow['page_id']);
			unset($dependentRow['id']);
		}
		$row               = $row->toArray();
		$row['content']    = ($rowTemplate !== null) ? $rowTemplate->content : '';
		$row['categories'] = $categories;
		$row               = array_merge($row, $dependentRow);
		unset($rowTemplate);
		return $row;
	}
}