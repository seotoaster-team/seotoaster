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
            throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_Models_UserWhitelistIp instance');
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

            return $this->getDbTable()->insert($data);
        } else {
            return $this->getDbTable()->update($data, array('id = ?' => $id));
        }
    }

}

