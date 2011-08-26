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
		if(!$featuredArea instanceof Application_Model_Models_Featuredarea) {
			throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_Models_Featuredarea instance');
		}

		$data = array(
			'name' => $featuredArea->getName()
		);

		//needs to be changed
		$pages = $featuredArea->getPages();
		$faPageDbTable =  new Application_Model_DbTable_PageFeaturedarea();

		$pagesToDelete = $featuredArea->getDeletedPages();
		foreach ($pagesToDelete as $pageId) {
			$faId = $featuredArea->getId();
			$faPageDbTable->delete('fa_id = ' . $faId . ' AND page_id = ' . $pageId);
		}


		foreach ($pages as $page) {
			$row = $faPageDbTable->createRow();
			$row->setFromArray(array(
				'page_id' => $page->getId(),
				'fa_id'   => $featuredArea->getId(),
				'order'   => 0
			));
			$result = $row->save();
		}


		if(null === ($id = $featuredArea->getId())) {
			unset($data['id']);
			return $this->getDbTable()->insert($data);
		}
		else {
			return $this->getDbTable()->update($data, array('id = ?' => $id));
		}
	}

	public function findByName($name) {
		//$row = $this->_findWhere($this->getDbTable()->getAdapter()->quoteInto("name=?", $name));
		$faPages = array();
		$row     = $this->getDbTable()->fetchAll($this->getDbTable()->getAdapter()->quoteInto("name=?", $name))->current();
		if($row === null) {
			return null;
		}

		$rowsPageFeaturedarea = $row->findDependentRowset('Application_Model_DbTable_PageFeaturedarea');

		$pageMapper = Application_Model_Mappers_PageMapper::getInstance();
		foreach ($rowsPageFeaturedarea as $key => $rowPageFa) {
			$faPages[] = $pageMapper->find($rowPageFa->page_id);
		}

		$featuredArea = new $this->_model($row->toArray());
		$featuredArea->setPages($faPages);
		return $featuredArea;
	}

	public function fetchAll($where = null, $order = array()) {
		$entries = array();
		$resultSet = $this->getDbTable()->fetchAll($where, $order);
		if(null === $resultSet) {
			return null;
		}
		$pages = array();
		foreach ($resultSet as $row) {
			$pagesRowset = $row->findManyToManyRowset('Application_Model_DbTable_Page', 'Application_Model_DbTable_PageFeaturedarea')->toArray();
			if(!empty ($pagesRowset)) {
				$pages = array();
				foreach($pagesRowset as $pageRow) {
					$pages[] = new Application_Model_Models_Page($pageRow);
				}
			}
			$data          = $row->toArray();
			$data['pages'] = $pages;
			$entries[] = new $this->_model($data);

		}
		return $entries;
	}

	public function findAreasByPageId($pageId) {
		$pageDbTable = new Application_Model_DbTable_Page();
		$pageRow     = $pageDbTable->find($pageId)->current();
		$areasRowset = $pageRow->findManyToManyRowset('Application_Model_DbTable_Featuredarea', 'Application_Model_DbTable_PageFeaturedarea');
		$areasRowset = $areasRowset->toArray();
		$areas       = array();
		if(!empty ($areasRowset)) {
			foreach ($areasRowset as $row) {
				$areas[] = new $this->_model($row);
			}
		}
		return $areas;
	}
}

