<?php
/**
 * EmailTriggersMapper
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 * @method Application_Model_Mappers_EmailTriggersMapper getInstance() getInstance() returns instance of itself
 * @method Application_Model_DbTable_TriggersActions getDbTable() getDbTable() returns instance of dbTable
 */
class Application_Model_Mappers_EmailTriggersMapper extends Application_Model_Mappers_Abstract  {

	const TRIGGER_STATUS_ENABLED    = '1';

	const TRIGGER_STATUS_DISABLED   = '0';

	protected $_dbTable = 'Application_Model_DbTable_TriggersActions';

	protected $_model = 'Application_Model_Models_TriggerAction';

	public function save($model) {
		if (!$model instanceof $this->_model) {
			$model = new $this->_model($model);
		}

		if ($model->getId()){
			$data = $model->toArray();
            unset($data['smsText']);
			unset($data['id']);
			$this->getDbTable()->update($data, array('id = ?' => $model->getId()));
		} else {
            $triggerAction = $model->toArray();
            unset($triggerAction['smsText']);
			$this->getDbTable()->insert($triggerAction);
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

	public function findByTriggerName($triggerName) {
		return $this->getDbTable()->fetchAll(array('`trigger` = ?' => $triggerName));
	}

	/**
	 * Registers plugin triggers with their observers
	 * @param $pluginName string Name of plugin
	 * @return Application_Model_Mappers_EmailTriggersMapper
	 */
	public function registerTriggers($pluginName) {
		$triggers = $this->_getTriggers($pluginName);
		if (!empty($triggers)) {
			$triggersTable = new Zend_Db_Table('email_triggers');
			$triggersTable->getAdapter()->beginTransaction();
			foreach ($triggers as $trigger){
				$trigger['enabled'] = self::TRIGGER_STATUS_ENABLED;
				$triggersTable->insert($trigger);
			}
			try{
				$triggersTable->getAdapter()->commit();
			} catch (Exception $e) {
				$triggersTable->getAdapter()->rollBack();
				error_log($e->getMessage());
				error_log($e->getTraceAsString());
			}
		}
		return $this;
	}

    public function registerTrigger($triggerName) {
        $triggersTable = new Zend_Db_Table('email_triggers');
        $triggersTable->getAdapter()->beginTransaction();
        $trigger['enabled']         = self::TRIGGER_STATUS_ENABLED;
        $trigger['trigger_name']    = $triggerName;
        $triggersTable->insert($trigger);
        try{
            $triggersTable->getAdapter()->commit();
        } catch (Exception $e) {
            $triggersTable->getAdapter()->rollBack();
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
        }
    }

	/**
	 * Unregisters (removes) plugin triggers with their observers
	 * @param $pluginName string Name of plugin
	 * @return Application_Model_Mappers_EmailTriggersMapper
	 */
	public function unregisterTriggers($pluginName) {
		$triggers = $this->_getTriggers($pluginName);
		if (!empty($triggers)) {
			$triggersTable = new Zend_Db_Table('email_triggers');

			foreach ($triggers as $trigger){
				$triggersTable->delete(array(
						'trigger_name = ?'  => $trigger['trigger_name'],
						'observer = ?'      => $trigger['observer']
				));
			}
		}
		return $this;
	}

	/**
	 * Switching trigger statuses according to plugin status
	 * @param $pluginName Name of plugin
	 * @param $status Plugin status
	 * @return Application_Model_Mappers_EmailTriggersMapper
	 */
	public function toggleTriggersStatuses($pluginName, $status){
		$triggers = $this->_getTriggers($pluginName);
		if (!empty($triggers)){
			$triggersTable = new Zend_Db_Table('email_triggers');
			$triggersTable->getAdapter()->beginTransaction();
			foreach ($triggers as $trigger) {
				$triggersTable->update(
					array(
						'enabled' => ($status == Application_Model_Models_Plugin::DISABLED ? self::TRIGGER_STATUS_DISABLED : self::TRIGGER_STATUS_ENABLED)
					)
					, array(
						'trigger_name = ?'  => $trigger['trigger_name'],
						'observer = ?'      => $trigger['observer']
				));
			}
			$triggersTable->getAdapter()->commit();
		}

		return $this;
	}

	/**
	 * Registers plugin custom recipients
	 * @param $pluginName
	 * @return Application_Model_Mappers_EmailTriggersMapper
	 */
	public function registerRecipients($pluginName) {
		$recipients = $this->_getRecipients($pluginName);
		if (is_array($recipients) && !empty($recipients)){
			$recipientsTable = new Zend_Db_Table('email_triggers_recipient');

			foreach ($recipients as $recipient) {
				if (null === ($row = $recipientsTable->fetchRow(array('recipient = ?' => $recipient)))){
					$row = $recipientsTable->insert(array(
						'recipient' => $recipient
					));
				}
			}

		}

		return $this;
	}

	/**
	 * Unregisters plugin custom recipients
	 * @param $pluginName
	 * @return Application_Model_Mappers_EmailTriggersMapper
	 */
	public function unregisterRecipients($pluginName) {
		$recipients = $this->_getRecipients($pluginName);
		if (is_array($recipients) && !empty($recipients)){
			$recipientsTable = new Zend_Db_Table('email_triggers_recipient');

			foreach ($recipients as $recipient) {
					$recipientsTable->delete(array('recipient = ?' => $recipient));
			}

		}

		return $this;
	}

	/**
	 * Fetch list of plugin email observers
	 * @param $pluginName
	 * @return mixed|null
	 */
	private function _getPluginObserversList($pluginName) {
        try {
            $pluginReflection = new Zend_Reflection_Class(Tools_Factory_PluginFactory::createPlugin($pluginName));
        } catch (Exceptions_SeotoasterPluginException $spe) {
            return null;
        }
        if ($pluginReflection->hasProperty(Tools_Mail_Watchdog::OBSERVER_LIST_PROP)){
            return $pluginReflection->getStaticPropertyValue(Tools_Mail_Watchdog::OBSERVER_LIST_PROP);
        }
		return null;
	}

	/**
	 * Parse observer class for contstants containing trigger names
	 * @param $pluginName
	 * @return array List of trigger-observer pairs
	 */
	private function _getTriggers($pluginName) {
		$triggers = array();

		$observers = $this->_getPluginObserversList($pluginName);

		if (is_array($observers) && !empty($observers)) {
			foreach ($observers as $observerName) {
				$reflection = new Zend_Reflection_Class($observerName);
				$propList = $reflection->getConstants();
				if (!empty($propList)){
					foreach ($propList as $constName => $trigger) {
						if (strpos($constName, 'TRIGGER_') !==  0) continue;
						$triggers[] = array(
							'trigger_name'  => $trigger,
							'observer'      => $reflection->getName()
						);
					}
				}
			}
		}
		return $triggers;
	}

	/**
	 * Parse observer class for contstants containing recipients names
	 * @param $pluginName
	 * @return array List of recipients
	 */
	private function _getRecipients($pluginName) {
		$triggers = array();

		$observers = $this->_getPluginObserversList($pluginName);

		if (is_array($observers) && !empty($observers)) {
			foreach ($observers as $observerName) {
				$reflection = new Zend_Reflection_Class($observerName);
				$propList = $reflection->getConstants();
				if (!empty($propList)){
					foreach ($propList as $constName => $trigger) {
						if (strpos($constName, 'RECIPIENT_') !==  0) continue;
						$triggers[] = $trigger;
					}
				}
			}
		}
		return $triggers;
	}

}
