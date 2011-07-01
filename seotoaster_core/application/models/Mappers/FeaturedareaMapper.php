<?php

/**
 * FeaturedareaMapper
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Model_Mappers_FeaturedareaMapper extends Application_Model_Mappers_Abstract {

	protected $_dbTable = 'Application_Model_DbTable_Featuredarea';

	protected $_model   = 'Application_Model_Models_Featuredarea';

	public function save($featuredArea) {

	}

	public function findByName($name) {
		//$row = $this->_findWhere($this->getDbTable()->getAdapter()->quoteInto("name=?", $name));
		$faPages = array();
		$row     = $this->getDbTable()->fetchAll($this->getDbTable()->getAdapter()->quoteInto("name=?", $name))->current();
		if($row === null) {
			return null;
		}
		$rowsPageFeaturedarea = $row->findDependentRowset('Application_Model_DbTable_PageFeaturedarea');

		$pageMapper = new Application_Model_Mappers_PageMapper();
		foreach ($rowsPageFeaturedarea as $key => $rowPageFa) {
			$faPages[] = $pageMapper->find($rowPageFa->page_id);
		}

		$featuredArea = new $this->_model($row->toArray());
		$featuredArea->setPages($faPages);
		return $featuredArea;
	}

}

