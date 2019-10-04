<?php

/**
 * Class Application_Model_Mappers_FormBlacklistRulesMapper
 * @method static Application_Model_Mappers_FormBlacklistRulesMapper getInstance() getInstance() Returns an instance of itself
 * @method Application_Model_DbTable_FormBlacklistRules getDbTable() Returns an instance of corresponding DbTable
 */
class Application_Model_Mappers_FormBlacklistRulesMapper extends Application_Model_Mappers_Abstract
{

    protected $_dbTable = 'Application_Model_DbTable_FormBlacklistRules';

    protected $_model = 'Application_Model_Models_FormBlacklistRules';

    public function save($model)
    {
        if (!$model instanceof Application_Model_Models_FormBlacklistRules) {
            throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_Models_User instance');
        }
        $data = array(
            'type' => $model->getType(),
            'value' => $model->getValue(),
        );

        if (null === ($id = $model->getId())) {
            unset($data['id']);

            return $this->getDbTable()->insert($data);
        } else {
            return $this->getDbTable()->update($data, array('id = ?' => $id));
        }
    }

    /**
     * get all with type email
     *
     * @param string $value
     * @return array
     */
    public function getByEmailType($value)
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto('type = ?',
            Application_Model_Models_FormBlacklistRules::RULE_TYPE_EMAIL);
        $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('value = ?', $value);

        $select = $this->getDbTable()->getAdapter()->select()->from('form_blacklist_rules')->where($where);
        return $this->getDbTable()->getAdapter()->fetchAll($select);
    }

    /**
     * get all with type ip address
     *
     * @param string $value
     * @return array
     */
    public function getByIpAddressType($value)
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto('type = ?',
            Application_Model_Models_FormBlacklistRules::RULE_TYPE_IP_ADDRESS);
        $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('value = ?', $value);

        $select = $this->getDbTable()->getAdapter()->select()->from('form_blacklist_rules')->where($where);
        return $this->getDbTable()->getAdapter()->fetchAll($select);
    }

    /**
     * get all with type domain
     *
     * @param string $value
     * @return array
     */
    public function getByDomainType($value)
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto('type = ?',
            Application_Model_Models_FormBlacklistRules::RULE_TYPE_DOMAIN);
        $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('value LIKE  ?', '%'.$value.'%');

        $select = $this->getDbTable()->getAdapter()->select()->from('form_blacklist_rules')->where($where);
        return $this->getDbTable()->getAdapter()->fetchAll($select);

    }


    /**
     * get all with html tags
     *
     * @return array
     */
    public function getByHtmlTags()
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto('type = ?',
            Application_Model_Models_FormBlacklistRules::RULE_TYPE_HTMLTAGS);

        $select = $this->getDbTable()->getAdapter()->select()->from('form_blacklist_rules')->where($where);
        return $this->getDbTable()->getAdapter()->fetchAll($select);

    }

    /**
     * Delete all data from table
     *
     * @throws Zend_Db_Table_Exception
     */
    public function deleteAllData()
    {
        $tableName = $this->getDbTable()->info(Zend_Db_Table::NAME);

        $this->getDbTable()->getAdapter()->query(
            'TRUNCATE TABLE ' . $tableName
        );
    }

}

