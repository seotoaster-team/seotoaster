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
		$this->_cleanPages($featuredArea->getId(), $featuredArea->getDeletedPages());
		$this->_addPages($featuredArea->getId(), $featuredArea->getPages());

		if(null === ($id = $featuredArea->getId())) {
			unset($data['id']);
			return $this->getDbTable()->insert($data);
		}
		else {
			return $this->getDbTable()->update($data, array('id = ?' => $id));
		}
	}

	private function _cleanPages($faId, $pages) {
		if(!empty($pages)) {
			$faPageDbTable = new Application_Model_DbTable_PageFeaturedarea();
			foreach ($pages as $pageId) {
				$faPageDbTable->delete('fa_id = ' . $faId . ' AND page_id = ' . $pageId);
			}
			unset($faPageDbTable);
		}
	}

	private function _addPages($faId, $pages) {
		if(!empty($pages)) {
			$faPageDbTable = new Application_Model_DbTable_PageFeaturedarea();
			foreach ($pages as $page) {
				$row = $faPageDbTable->createRow();
				$row->setFromArray(array(
					'page_id' => $page->getId(),
					'fa_id'   => $faId,
					'order'   => 0
				));
				$result = $row->save();
			}
			unset($faPageDbTable);
		}
	}

	public function find($id, $loadPages = true) {
		$faPages = array();
		$result  = $this->getDbTable()->find($id);
		if(0 == count($result)) {
			return null;
		}
		$row = $result->current();
		$featuredArea = new $this->_model($row->toArray());

		if($loadPages) {
			$featuredArea->setPages($this->_findFarowPages($row));
		}

		return $featuredArea;
	}

	public function findByName($name, $loadPages = true) {
		$row     = $this->getDbTable()->fetchAll($this->getDbTable()->getAdapter()->quoteInto("name=?", $name))->current();
		if($row === null) {
			return null;
		}
		$featuredArea = new $this->_model($row->toArray());

		if($loadPages) {
			$featuredArea->setPages($this->_findFarowPages($row));
		}

		return $featuredArea;
	}

	public function fetchAll($where = null, $order = array()) {
		$entries = array();
		$resultSet = $this->getDbTable()->fetchAll($where, $order);
		if(null === $resultSet) {
			return null;
		}
		foreach ($resultSet as $row) {
			$pages = array();
			$pagesRowset = $row->findManyToManyRowset('Application_Model_DbTable_Page', 'Application_Model_DbTable_PageFeaturedarea')->toArray();
			if(!empty ($pagesRowset)) {
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

	private function _findFarowPages($faRow) {
		$faPages = array();
		$rowsPageFeaturedarea = $faRow->findDependentRowset('Application_Model_DbTable_PageFeaturedarea');
		foreach ($rowsPageFeaturedarea as $key => $rowPageFa) {
			$order           = array_key_exists($rowPageFa->order, $faPages) ? (array_search(end($faPages), $faPages)) + 1 : $rowPageFa->order;
			$faPages[$order] = Application_Model_Mappers_PageMapper::getInstance()->find($rowPageFa->page_id);
		}
		return $faPages;
	}

	public function findAreasByPageId($pageId, $loadPages = false) {
		$pageDbTable = new Application_Model_DbTable_Page();
		$pageRow     = $pageDbTable->find($pageId)->current();
		$areasRowset = $pageRow->findManyToManyRowset('Application_Model_DbTable_Featuredarea', 'Application_Model_DbTable_PageFeaturedarea');
		//$areasRowset = $areasRowset->toArray();
		$areas       = array();
		if($areasRowset) {
			foreach ($areasRowset as $row) {
				$farea =  new $this->_model($row->toArray());
				if($loadPages) {
					$farea->setPages($this->_findFarowPages($row));
				}
				$areas[] = $farea;
			}
		}
		return $areas;
	}

	public function saveFaOrder($ordered, $faId) {
		$relationTable = new Application_Model_DbTable_PageFeaturedarea();
		foreach ($ordered as $order => $pageId) {
			$data = array(
				'order' => $order
			);
			$relationTable->update($data, 'page_id = ' . $pageId . ' AND fa_id = ' . $faId);
		}
	}

	public function delete(Application_Model_Models_Featuredarea $featuredArea) {
		$where        = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $featuredArea->getId());
		$deleteResult = $this->getDbTable()->delete($where);
		$featuredArea->notifyObservers();
		return $deleteResult;
	}
}

