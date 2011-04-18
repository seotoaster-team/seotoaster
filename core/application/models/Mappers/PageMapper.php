<?php

class Application_Model_Mappers_PageMapper extends Application_Model_Mappers_Abstract {

	protected $_dbTable = 'Application_Model_DbTable_Page';

	public function save($page) {
		if(!$page instanceof Application_Model_Models_Page) {
			throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_Models_Page instance');
		}
		$data = array(
			'parent_id'           => $page->getParentId(),
			'h1'                  => $page->getH1(),
			'header_title'        => $page->getHeaderTitle(),
			'url'                 => $page->getUrl(),
			'nav_name'            => $page->getNavName(),
			'meta_description'    => $page->getMetaDescription(),
			'meta_keywords'       => $page->getMetaKeywords(),
			'teaser_text'         => $page->getTeaserText(),
			'show_in_menu'        => $page->getShowInMenu(),
			'is_404page'          => $page->getIs404page(),
			'protected'           => $page->getProtected(),
			'order'               => $page->getOrder(),
			'static_order'        => $page->getStaticOrder(),
			'targeted_key_phrase' => $page->getTargetedKey()
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
		$result = $this->getDbTable()->find($id);
		if(0 == count($result)) {
			return null;
		}
		$row = $result->current();
		return new Application_Model_Models_Page($row->toArray());
	}

    public function fetchAll($where = '') {
		//exclude system pages from select
		$where .= $this->getDbTable()->getAdapter()->quoteInto(' AND system = "?"', 0);
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

	public function selectCategoriesIdName() {
		$result     = array();
		$categories = $this->findByParentId(0);
		foreach ($categories as $key => $category) {
			$categoryName = ($category->getProtected()) ? ($category->getH1() . '*') : $category->getH1();
			$result[$category->getId()] = $categoryName;
		}
		return $result;
	}
}

