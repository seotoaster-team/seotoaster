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

		foreach ($pages as $page) {
			$row = $faPageDbTable->createRow();
			$row->setFromArray(array(
				'page_id' => $page->getId(),
				'fa_id'   => $featuredArea->getId(),
				'order'   => 0
			));
			$result = $row->save();
		}
		//

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

		$pageMapper = new Application_Model_Mappers_PageMapper();
		foreach ($rowsPageFeaturedarea as $key => $rowPageFa) {
			$faPages[] = $pageMapper->find($rowPageFa->page_id);
		}

		$featuredArea = new $this->_model($row->toArray());
		$featuredArea->setPages($faPages);
		return $featuredArea;
	}

}

