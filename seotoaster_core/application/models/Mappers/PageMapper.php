<?php

class Application_Model_Mappers_PageMapper extends Application_Model_Mappers_Abstract {

	protected $_dbTable = 'Application_Model_DbTable_Page';

	protected $_model   = 'Application_Model_Models_Page';

	public function save($page) {
		if(!$page instanceof Application_Model_Models_Page) {
			throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_Models_Page instance');
		}
		$data = array(
			'template_id'         => $page->getTemplateId(),
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
			'targeted_key_phrase' => $page->getTargetedKey(),
			'system'              => $page->getSystem()
		);
		if(null === ($id = $page->getId())) {
			unset($data['id']);
			$this->getDbTable()->insert($data);
		}
		else {
			$this->getDbTable()->update($data, array('id = ?' => $id));
		}
	}

    public function fetchAll($where = '', $order = array()) {
		//exclude system pages from select
		if($where) {
			$where .= $this->getDbTable()->getAdapter()->quoteInto(' AND system = "?"', 0);
		}
		else {
			$where = $this->getDbTable()->getAdapter()->quoteInto('system = "?"', 0);
		}

		$order[] = 'order';

		$entries = array();
		$resultSet = $this->getDbTable()->fetchAll($where, $order);
		if(null === $resultSet) {
			return null;
		}
		foreach ($resultSet as $row) {
			$entries[] = new $this->_model($row->toArray());
		}
		return $entries;
	}

	public function fetchAllUrls() {
		$urls  = array();
		$pages = $this->fetchAll();
		foreach ($pages as $page) {
			$urls[] = $page->getUrl();
		}
		return $urls;
	}

	public function fetchAllStaticMenuPages() {
		$where = $this->getDbTable()->getAdapter()->quoteInto("show_in_menu = '?'", Application_Model_Models_Page::IN_STATICMENU);
		return $this->fetchAll($where);
	}

	public function fetchAllMainMenuPages() {
		$where = $this->getDbTable()->getAdapter()->quoteInto("show_in_menu = '?'", Application_Model_Models_Page::IN_MAINMENU);
		return $this->fetchAll($where);
	}

	public function fetchAllDraftPages() {
		$where = $this->getDbTable()->getAdapter()->quoteInto("parent_id = ?", Application_Model_Models_Page::IDCATEGORY_DRAFT);
		return $this->fetchAll($where);
	}

	public function fetchAllNomenuPages() {
		$where = sprintf("show_in_menu = '%s' AND parent_id = %d", Application_Model_Models_Page::IN_NOMENU, Application_Model_Models_Page::IDCATEGORY_DEFAULT);
		return $this->fetchAll($where);
	}

	public function findByUrl($pageUrl) {
		$where = $this->getDbTable()->getAdapter()->quoteInto('url = ?', $pageUrl);
		return $this->_findWhere($where);
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

	public function find404Page() {
		$where  = $this->getDbTable()->getAdapter()->quoteInto('is_404page = ?', '1');
		return $this->_findWhere($where);
	}

	public function delete(Application_Model_Models_Page $page) {
		$where = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $page->getId());
		$deleteResult = $this->getDbTable()->delete($where);
		$page->notifyObservers();
		return $deleteResult;
	}

	public function fetchIdUrlPairs() {
		$pairs = array();
		$pages = $this->fetchAll();
		if(empty($pages)) {
			return null;
		}
		foreach ($pages as $page) {
			$pairs[$page->getId()] = $page->getUrl();
		}
		asort($pairs);
		return $pairs;
	}

	protected function _findWhere($where) {
		$row    = $this->getDbTable()->fetchAll($where)->current();
		if(null === $row) {
			return null;
		}
		$rowTemplate = $row->findParentRow('Application_Model_DbTable_Template');
		$row = $row->toArray();
		$row['content'] = ($rowTemplate !== null) ? $rowTemplate->content : '';
		unset($rowTemplate);
		return new Application_Model_Models_Page($row);
	}
}

