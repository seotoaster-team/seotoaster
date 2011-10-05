<?php

/**
 * NewsCategoryMapper
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Model_Mappers_NewsCategoryMapper extends Application_Model_Mappers_Abstract {

	protected $_model   = 'Application_Model_Models_NewsCategory';

	protected $_dbTable = 'Application_Model_DbTable_NewsCategory';

	public function findByName($name, $loadNews = false) {
		$where      = $this->getDbTable()->getAdapter()->quoteInto("name = ?", $name);
		$newsCatRow = $this->getDbTable()->fetchAll($where)->current();
		if($newsCatRow === null) {
			return null;
		}
		$newsCategory = new $this->_model($newsCatRow->toArray());
		if($loadNews) {
			$newsCategory->setNewsItems($this->_findNewsItems($newsCatRow));
		}
		return $newsCategory;
	}

	public function save($newsCategory) {

	}

	public function fetchAll($where = null, $order = array()) {
		$entries = array();
		$resultSet = $this->getDbTable()->fetchAll($where, $order);
		if(null === $resultSet) {
			return null;
		}
		foreach ($resultSet as $row) {
			$newsItems    = $this->_findNewsItems($row);
			$newsCategory = new $this->_model($row->toArray());
			$entries[]    = $newsCategory->setNewsItems($newsItems);
		}
		return $entries;
	}

	private function _findNewsItems($newsCatRow) {
		$rowset = $newsCatRow->findManyToManyRowset('Application_Model_DbTable_News', 'Application_Model_DbTable_NewsRelCategory');
		if(!$rowset) {
			return array();
		}
		$newsItems = array();
		foreach ($rowset as $row) {
			$pagePart = $row->findParentRow('Application_Model_DbTable_Page')->toArray();
			$newsPart = $row->toArray();
			unset($newsPart['id']);
			unset($newsPart['page_id']);
			$newsItems[] = new Application_Model_Models_News(array_merge($pagePart, $newsPart));
		}
		return $newsItems;
	}
}

