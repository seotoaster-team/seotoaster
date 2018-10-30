<?php

/**
 * FeaturedareaMapper
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
*
 * Class Application_Model_Mappers_FeaturedareaMapper
 * @method Application_Model_Mappers_FeaturedareaMapper getInstance() getInstance()  Returns an instance of itself
 * @method Application_Model_DbTable_Featuredarea getDbTable() getDbTable() Returns an instance of corresponding DbTable
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
            $pagesWithFarea = $faPageDbTable->fetchAll($this->getDbTable()->getAdapter()->quoteInto("fa_id=?", $faId), 'order DESC');
            $pagesOrderForFarea = $pagesWithFarea->toArray();
            $order = empty($pagesOrderForFarea) ? 0 : $pagesOrderForFarea[0]['order'] + 1;
            foreach ($pages as $page) {
				$row = $faPageDbTable->createRow();
				$row->setFromArray(array(
					'page_id' => $page->getId(),
					'fa_id'   => $faId,
					'order'   => $order
				));
				$result = $row->save();
                $order = $order + 1;
			}
			unset($faPageDbTable);
		}
	}

	public function find($id, $loadPages = true) {
		$result  = $this->getDbTable()->find($id);
		if(0 == count($result)) {
			return null;
		}
		$row = $result->current();
		$featuredArea = new $this->_model($row->toArray());

		if($loadPages) {
			$featuredArea->setPages($this->_findFarowPages($featuredArea));
		}

		return $featuredArea;
	}

	public function findByName($name, $loadPages = true, $order = false, $orderType = 'ASC') {
		$row = $this->getDbTable()->fetchRow($this->getDbTable()->getAdapter()->quoteInto('name = ?', $name));
		if ($row === null) {
			return null;
		}

		$featuredArea = new $this->_model($row->toArray());
		if ($loadPages) {
			$featuredArea->setPages($this->_findFarowPages($featuredArea, $order, $orderType));
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
            $query =  $this->_dbTable->getAdapter()->select()
                ->from(array('p' => 'page'))
                ->joinleft(array('pf' => 'page_fa'), 'p.id = pf.page_id')
                ->where('fa_id=?', $row['id']);
            $pagesRowset   = $this->_dbTable->getAdapter()->fetchAll($query);
            if(!empty ($pagesRowset)) {
                $pages = array_map( function($page) { return new Application_Model_Models_Page($page); }, $pagesRowset);
            $data          = $row->toArray();
            $data['pages'] = $pages;
            $entries[]     = new $this->_model($data);
            }
        }
        return $entries;
    }

    public function fetchFaList() {
        $entries = array();
        $resultSet = $this->getDbTable()->fetchAll();
        if(null === $resultSet) {
            return null;
        }
        foreach ($resultSet as $row) {
            $query =  $this->_dbTable->getAdapter()->select()
                ->from(array('p' => 'page'), array('id' => 'p.id'))
                ->joinleft(array('pf' => 'page_fa'), 'p.id = pf.page_id', array())
                ->where('fa_id=?', $row['id']);
            $pagesRowset   = $this->_dbTable->getAdapter()->fetchAll($query);
                $data          = $row->toArray();
                $data['pages'] = $pagesRowset;
                $entries[]     = new $this->_model($data);
        }
        return $entries;
    }

    private function _findFarowPages($faModel, $order = false, $orderType = 'ASC')
    {
        $faPageDbTable = new Application_Model_DbTable_PageFeaturedarea();
        if (!$order) {
            $pageIds = $faPageDbTable->getAdapter()->fetchCol(
                $faPageDbTable->select()->where('fa_id = ?', $faModel->getId())->order('order ASC')
            );
        } else {
            $pageIds = $faPageDbTable->getAdapter()->fetchCol($faPageDbTable->getAdapter()->select()
                ->from(array('p' => 'page'), array('id' => 'p.id'))
                ->joinleft(array('pf' => 'page_fa'), 'p.id = pf.page_id', array())
                ->where('fa_id IN (?)', $faModel->getId())->order('p.'.$order.' '.$orderType));
        }
        unset($faPageDbTable);

        $faPages = array();
        if (!empty($pageIds)) {
            $pageIds = implode(',', $pageIds);
            $faPages = Application_Model_Mappers_PageMapper::getInstance()
                ->fetchAll('id IN ('.$pageIds.") AND system = '0'", array('FIELD(id, '.$pageIds.')'));
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
					$farea->setPages($this->_findFarowPages($farea));
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

