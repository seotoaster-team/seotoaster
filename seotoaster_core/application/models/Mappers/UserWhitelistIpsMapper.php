<?php

/**
 * Class Application_Model_Mappers_UserWhitelistIpsMapper
 * @method static Application_Model_Mappers_UserWhitelistIpsMapper getInstance() getInstance() Returns an instance of itself
 * @method Application_Model_DbTable_UserWhitelistIps getDbTable() Returns an instance of corresponding DbTable
 */
class Application_Model_Mappers_UserWhitelistIpsMapper extends Application_Model_Mappers_Abstract
{

    protected $_dbTable = 'Application_Model_DbTable_UserWhitelistIps';

    protected $_model = 'Application_Model_Models_UserWhitelistIp';

    public function save($model)
    {
        if (!$model instanceof Application_Model_Models_UserWhitelistIp) {
            throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_Models_UserWhitelistIp instance');
        }
        $data = array(
            'role_id' => $model->getRoleId(),
            'ipaddress' => $model->getIpAddress(),
        );

        if (null === ($id = $model->getId())) {
            unset($data['id']);

            return $this->getDbTable()->insert($data);
        } else {
            return $this->getDbTable()->update($data, array('id = ?' => $id));
        }
    }

    /**
     * Check if at least one record exists
     *
     * @return mixed
     */
    public function checkIfRecordsExists()
    {
        $select = $this->getDbTable()->getAdapter()->select()->from('user_whitelist_ips')->limit(1);
        return $this->getDbTable()->getAdapter()->fetchRow($select);
    }

    /**
     * find whitelisted ip address
     *
     * @param string $roleId system role id
     * @param string $ipAddress ip address
     * @return Application_Model_Models_UserWhitelistIp|null
     */
    public function findWhiteListedIp($ipAddress, $roleId)
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto('role_id = ?', $roleId);
        $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('ip_address = ?', $ipAddress);

        return $this->_findWhere($where);
    }


}

