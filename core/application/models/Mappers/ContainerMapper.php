<?php

/**
 * Container mapper
 *
 * @author Seotoaster Dev Team
 */
class Application_Model_Mappers_ContainerMapper extends Application_Model_Mappers_Abstract {

	protected $_dbTable = 'Application_Model_DbTable_Container';

	public function save($container) {
		if(!$container instanceof Application_Model_Models_Container) {
			throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_Models_Container instance');
		}
		$data = array(
			'name'            => $container->getName(),
			'content'         => $container->getContent(),
			'container_type'  => $container->getContainerType(),
			'page_id'         => $container->getPageId(),
			'published'       => $container->getPublished(),
			'publishing_date' => $container->getPublishingDate()
		);
		if(!$container->getId()) {
			return $this->getDbTable()->insert($data);
		}
		else {
			return $this->getDbTable()->update($data, array('id = ?' => $container->getId()));
		}
	}

    public function find($id) {
		$result = $this->getDbTable()->find($id);
		if(0 == count($result)) {
			return null;
		}
		$row = $result->current();
		return new Application_Model_Models_Container($row->toArray());
	}

	public function findByName($name, $pageId, $type = Application_Model_Models_Container::TYPE_REGULARCONTENT) {
		$where = $this->getDbTable()->getAdapter()->quoteInto('name = ?', $name);
		$where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('container_type = ?', $type);
		$where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('page_id = ?', $pageId);
		$row   = $this->getDbTable()->fetchAll($where)->current();
		if(null == $row) {
			return null;
		}
		return new Application_Model_Models_Container($row->toArray());
	}

    public function fetchAll() {
		$entries = array();
		$resultSet = $this->getDbTable()->fetchAll();
		if(null === $resultSet) {
			return null;
		}
		foreach ($resultSet as $row) {
			$entries[] = new Application_Model_Models_Container($row->toArray());
		}
		return $entries;
	}
}

