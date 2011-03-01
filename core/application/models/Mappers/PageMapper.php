<?php

class Application_Model_Mappers_PageMapper extends Application_Model_Mappers_Abstract {

	protected $_dbTable = 'Application_Model_DbTable_Page';

	public function save($page) {
		if(!$page instanceof Application_Model_Models_Page) {
			throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_Models_Page instance');
		}
		$data = array(
			//here all page data
		);
		if(null === ($id = $page->getId())) {
			unset($data['id']);
			$this->getDbTable()->insert($data);
		}
		else {
			$this->getDbTable()->update($data, array('id = ?' => $id));
		}
	}

    public function find($id) {
		
	}

    public function fetchAll($where = '') {
		$entries = array();
		$resultSet = $this->getDbTable()->fetchAll($where);
		if(null === $resultSet) {
			return null;
		}
		foreach ($resultSet as $row) {
			$entries[] = new Application_Model_Models_Page($row->toArray());
		}
		return $entries;
	}

	public function fetchAllStaticMenuPages() {
		$where = $this->getDbTable()->getAdapter()->quoteInto('show_in_menu = \'?\'', Application_Model_Models_Page::IN_STATICMENU);
		return $this->fetchAll($where);
	}

	public function fetchAllMainMenuPages() {
		$where = $this->getDbTable()->getAdapter()->quoteInto('show_in_menu = \'?\'', Application_Model_Models_Page::IN_MAINMENU);
		return $this->fetchAll($where);
	}


	public function findByUrl($pageUrl) {
		$where = $this->getDbTable()->getAdapter()->quoteInto('url = ?', $pageUrl);
		$row = $this->getDbTable()->fetchAll($where)->current();
		if(null == $row) {
			return null;
		}
		$rowTemplate = $row->findParentRow('Application_Model_DbTable_Template');
		$row = $row->toArray();
		$row['content'] = $rowTemplate->content;
		unset($rowTemplate);
		return new Application_Model_Models_Page($row);
	}

	public function findByParentId($parentId) {
		$where = $this->getDbTable()->getAdapter()->quoteInto('parent_id = ?', $parentId);
		return $this->fetchAll($where);
	}
}

