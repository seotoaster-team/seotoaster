<?php

/**
 * DeeplinkMapper
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Model_Mappers_DeeplinkMapper extends Application_Model_Mappers_Abstract {

	protected $_dbTable = 'Application_Model_DbTable_Deeplink';

	protected $_model   = 'Application_Model_Models_Deeplink';

	public function save($deeplink) {
		if(!$deeplink instanceof Application_Model_Models_Deeplink) {
			throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_DbTable_Deeplink instance');
		}
		$data = array(
			'page_id'  => $deeplink->getPageId(),
			'name'     => $deeplink->getName(),
			'url'      => $deeplink->getUrl(),
			'type'     => $deeplink->getType(),
			'ban'      => $deeplink->getBanned(),
			'nofollow' => $deeplink->getNofollow()
		);
		if(null === ($id = $deeplink->getId())) {
			unset($data['id']);
			$this->getDbTable()->insert($data);
		}
		else {
			$this->getDbTable()->update($data, array('id = ?' => $id));
		}
	}

    public function delete(Application_Model_Models_Deeplink $deeplink) {
		$where = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $deeplink->getId());
		$deleteResult = $this->getDbTable()->delete($where);
		$deeplink->notifyObservers();
	}

	public function findByPageId($pageId) {
		$where = $this->getDbTable()->getAdapter()->quoteInto('page_id = ?', $pageId);
		return $this->fetchAll($where);
	}
}

