<?php
/**
 * EmailTriggersMapper
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 * @method Application_Model_Mappers_EmailTriggersMapper getInstance() getInstance() returns instance of itself
 * @method Application_Model_DbTable_TriggersActions getDbTable() getDbTable() returns instance of dbTable
 */
class Application_Model_Mappers_EmailTriggersMapper extends Application_Model_Mappers_Abstract  {

	protected $_dbTable = 'Application_Model_DbTable_TriggersActions';

	protected $_model = 'Application_Model_Models_TriggerAction';

	public function save($model) {
		if (!$model instanceof $this->_model) {
			$model = new $this->_model($model);
		}

		if ($model->getId()){
			$data = $model->toArray();
			unset($data['id']);
			$this->getDbTable()->update($data, array('id = ?' => $model->getId()));
		} else {
			$this->getDbTable()->insert($model->toArray());
		}
	}

	public function delete($model) {
		if (!$model instanceof $this->_model){
			$model = (array) $model;
			$this->getDbTable()->delete(array('id IN (?)' => $model));
		}

	}

	public function getTriggers($pairs = false) {
		$triggersTable = new Zend_Db_Table('email_triggers');
		if ($pairs) {
			$select = $triggersTable->select()->from('email_triggers', array('id', 'trigger_name'))->distinct(true);
			return $triggersTable->getAdapter()->fetchPairs($select);
		} else {
			return $triggersTable->fetchAll()->toArray();
		}
	}

	public function getReceivers($pairs = false){
		$triggersTable = new Zend_Db_Table('email_triggers_recipient');
		if ($pairs) {
			$select = $triggersTable->select();
			return $triggersTable->getAdapter()->fetchPairs($select);
		} else {
			return $triggersTable->fetchAll()->toArray();
		}
	}

	public function fetchArray($where = null, $order = array(), $limit = null, $offset = null) {
		return $this->getDbTable()->fetchAll($where, $order, $limit, $offset)->toArray();
	}


}
