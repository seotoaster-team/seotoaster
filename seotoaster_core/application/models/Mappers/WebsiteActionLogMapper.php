<?php

/**
 * Class Application_Model_WebsiteActionLogMapper
 * @method static Application_Model_Mappers_WebsiteActionLogMapper getInstance() getInstance() Returns an instance of itself
 * @method Application_Model_DbTable_WebsiteActionLog getDbTable() Returns an instance of corresponding DbTable
 */
class Application_Model_Mappers_WebsiteActionLogMapper extends Application_Model_Mappers_Abstract
{

    protected $_dbTable = 'Application_Model_DbTable_WebsiteActionLog';

    protected $_model = 'Application_Model_Models_WebsiteActionLog';

    public function save($model)
    {
        if (!$model instanceof Application_Model_Models_WebsiteActionLog) {
            throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_Models_WebsiteActionLog instance');
        }
        $data = array(
            'created_at' => $model->getCreatedAt(),
            'ip_address' => $model->getIpAddress(),
            'email' => $model->getEmail(),
            'action_type' => $model->getActionType(),
            'name' => $model->getName(),
            'browser_fingerprint' => $model->getBrowserFingerprint(),
            'raw_data' => $model->getRawData(),
        );

        if (null === ($id = $model->getId())) {
            unset($data['id']);

            $id = $this->getDbTable()->insert($data);
            $model->setId($id);
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }

        return $model;
    }

    /**
     * Get activity records
     *
     * @param string $dateFrom starting from date
     * @param string $dateTo starting from date
     * @param string $threshold threshold
     * @return array
     */
    public function getSuspiciousActivityRecords($dateFrom, $dateTo, $threshold = 30)
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto('created_at >= ?', $dateFrom);
        $where .= ' AND '. $this->getDbTable()->getAdapter()->quoteInto('created_at < ?', $dateTo);

        $select = $this->getDbTable()->getAdapter()->select()
            ->from('website_action_log', array('action_type', 'name',
                'count' => 'COUNT(*)'))
            ->group(array('action_type', 'name'))
            ->where($where)
            ->having('COUNT(*) >= '.$threshold);

        $data = $this->getDbTable()->getAdapter()->fetchAll($select);

        return $data;
    }

    /**
     * Get by action type/name
     *
     * @param string $actionType action type
     * @param string $name name
     * @param string $dateFrom starting from date
     * @param string $dateTo starting from date
     * @return array
     */
    public function getByActionTypeName($actionType, $name, $dateFrom, $dateTo)
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto('action_type = ?', $actionType);
        $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('name = ?', $name);
        $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('created_at >= ?', $dateFrom);
        $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('created_at < ?', $dateTo);

        $select = $this->getDbTable()->getAdapter()->select()
            ->from('website_action_log', array('action_type', 'name', 'ip_address', 'browser_fingerprint', 'raw_data', 'id'))
            ->where($where);

        return $this->getDbTable()->getAdapter()->fetchAll($select);
    }

}

