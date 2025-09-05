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

            return $this->getDbTable()->insert($data);
        } else {
            return $this->getDbTable()->update($data, array('id = ?' => $id));
        }
    }

}

