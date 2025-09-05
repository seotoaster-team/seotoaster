<?php

/**
 * Class Application_Model_WebsiteVisitorsBacklogMapper
 * @method static Application_Model_Mappers_WebsiteVisitorsBacklogMapper getInstance() getInstance() Returns an instance of itself
 * @method Application_Model_DbTable_WebsiteVisitorsBacklog getDbTable() Returns an instance of corresponding DbTable
 */
class Application_Model_Mappers_WebsiteVisitorsBacklogMapper extends Application_Model_Mappers_Abstract
{

    protected $_dbTable = 'Application_Model_DbTable_WebsiteVisitorsBacklog';

    protected $_model = 'Application_Model_Models_WebsiteVisitorsBacklog';

    public function save($model)
    {
        if (!$model instanceof Application_Model_Models_WebsiteVisitorsBacklog) {
            throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_Models_WebsiteVisitorsBacklog instance');
        }
        $data = array(
            'created_at' => $model->getCreatedAt(),
            'ip_address' => $model->getIpAddress(),
            'action_type' => $model->getActionType(),
            'reason' => $model->getReason(),
            'valid_until' => $model->getValidUntil(),
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
     * @param string $ipAddress ip-address
     * @param string $date mysql date
     * @param array $actionTypes action types 'block'|'cooldown
     * @return string
     */
    public function isActiveBlock($ipAddress, $date, $actionTypes)
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto('ip_address = ?', $ipAddress);
        $where .= ' AND '. $this->getDbTable()->getAdapter()->quoteInto('valid_until > ?', $date);
        $where .= ' AND '. $this->getDbTable()->getAdapter()->quoteInto('action_type IN (?)', $actionTypes);
        // Check active block for this IP
        $select = $this->getDbTable()->getAdapter()->select()
            ->from('website_visitors_backlog')->where($where);
        $select->limit(1);

        return $this->getDbTable()->getAdapter()->fetchOne($select);
    }

}

