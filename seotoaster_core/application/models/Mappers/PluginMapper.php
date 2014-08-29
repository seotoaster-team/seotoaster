<?php

class Application_Model_Mappers_PluginMapper extends Application_Model_Mappers_Abstract {

	protected $_dbTable = 'Application_Model_DbTable_Plugin';

	protected $_model   = 'Application_Model_Models_Plugin';

	public function save($plugin , $notify = true) {
		if(!$plugin instanceof Application_Model_Models_Plugin) {
			throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_Models_Plugin instance');
		}
		$data = array(
			'name'     => $plugin->getName(),
			'status'   => $plugin->getStatus(),
			'tags'     => $plugin->getTags(true),
			'license'  => $plugin->getLicense(),
            'version'  => $plugin->getVersion()
		);
		if(!$plugin->getId()) {
			$status = $this->getDbTable()->insert($data);
		}
		else {
			$status = $this->getDbTable()->update($data, array('id = ?' => $plugin->getId()));
		}
        if ($notify === true) {
		    $plugin->notifyObservers();
        }
		return  $status;
	}

    /**
     * Find plugin by name
     *
     * @param string $name
     * @return Application_Model_Models_Plugin|null
     */
    public function findByName($name) {
		$where = $this->getDbTable()->getAdapter()->quoteInto('name = ?', $name);
		$row   = $this->getDbTable()->fetchAll($where)->current();
		if(null == $row) {
			return null;
		}
		return new Application_Model_Models_Plugin($row->toArray());
	}

	public function findEnabled() {
		$entries = array();
		$where   = $this->getDbTable()->getAdapter()->quoteInto('status = ?', Application_Model_Models_Plugin::ENABLED);
		$rowSet  = $this->getDbTable()->fetchAll($where);
		if(null == $rowSet) {
			return null;
		}
		foreach ($rowSet as $row) {
			$entries[] = new Application_Model_Models_Plugin($row->toArray());
		}
		return $entries;
	}

	public function delete(Application_Model_Models_Plugin $plugin) {
		$where = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $plugin->getId());
		$deleteResult = $this->getDbTable()->delete($where);
		$plugin->notifyObservers();
	}

	public function deleteByName(Application_Model_Models_Plugin $plugin) {
		$where = $this->getDbTable()->getAdapter()->quoteInto('name = ?', $plugin->getName());
		$deleteResult = $this->getDbTable()->delete($where);
		$plugin->notifyObservers();
	}

    public function getPluginDataById($id){
        $where = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $id);
        $rowSet =  $this->getDbTable()->fetchAll($where);
        if(null == $rowSet) {
            return null;
        }
        foreach ($rowSet as $row) {
            $entries[] =$row->toArray();
        }
        return $entries[0];
    }
}

