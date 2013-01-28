<?php
/**
 * PageOptionMapper
 * @author: iamne <eugene@seotoaster.com> Seotoaster core team
 * Date: 7/27/12
 * Time: 3:55 PM
 */
class Application_Model_Mappers_PageOptionMapper extends Application_Model_Mappers_Abstract {

    protected $_dbTable = 'Application_Model_DbTable_PageOption';

    public function save($model) {

    }

    public function fetchOptions($activeOnly = false) {
        $where = '';
        if($activeOnly) {
            $where = $this->getDbTable()->getAdapter()->quoteInto('active=?', true);
        }
        return $this->fetchAll($where);
    }

    public function fetchAll($where = '', $order = array()) {
        $select = $this->getDbTable()->select();
        if($where) {
            $select->where($where);
        }
        $optionsRowset = $this->getDbTable()->fetchAll($select);
        return $optionsRowset->toArray();
    }
}