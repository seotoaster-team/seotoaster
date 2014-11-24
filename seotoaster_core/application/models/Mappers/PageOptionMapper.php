<?php
/**
 * PageOptionMapper
 * @author: iamne <eugene@seotoaster.com> Seotoaster core team
 * Date: 7/27/12
 * Time: 3:55 PM
 */
class Application_Model_Mappers_PageOptionMapper extends Application_Model_Mappers_Abstract {

    protected $_dbTable = 'Application_Model_DbTable_PageOption';

    protected $_model   = 'Application_Model_Models_PageOption';

    public function save($model) {
        if(!$model instanceof $this->_model) {
            if(is_array($model) && !empty($model)) {
                $model = new Application_Model_Models_PageOption($model);
            } else {
                throw new Exceptions_SeotoasterException('Instance of the ' . $this->_model . ' expected. ' . get_class($model) . ' given.');
            }
        }

        $data = array(
            'title'   => $model->getTitle(),
            'context' => $model->getContext(),
            'active'  => $model->getActive()
        );

        if($this->find($model->getId())) {
            $this->getDbTable()->update($data, array('id=?' => $model->getId()));
        } else {
            $data['id'] = $model->getId();
            $this->getDbTable()->insert($data);
        }
        $model->notifyObservers();
        return $model;
    }

    public function fetchOptions($activeOnly = false, $pairs = false) {
        $where = '';
        if($activeOnly) {
            $where = $this->getDbTable()->getAdapter()->quoteInto('active=?', true);
        }
        if($pairs){
            $select = $this->getDbTable()->select();
            return $this->getDbTable()->getAdapter()->fetchAssoc($select);
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

    public function checkOptionUsage($optionId, $pageUrl, $once = Application_Model_Models_Page::OPTION_USAGE_ONCE)
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto('po.id=?', $optionId);
        $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('po.option_usage=?', $once);
        $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('p.url <> ?', $pageUrl);
        $select = $this->getDbTable()->select()->setIntegrityCheck(false)
            ->from(array('po' => 'page_option'))
            ->joinLeft(array('pho' => 'page_has_option'), 'po.id = pho.option_id')
            ->joinLeft(array('p' => 'page'), 'p.id = pho.page_id')
            ->where($where);
        return $this->getDbTable()->getAdapter()->fetchRow($select);
    }

    public function deletePageHasOption($optionId)
    {
        $pageHasOptionDbTable = new Application_Model_DbTable_PageHasOption();
        $where = $pageHasOptionDbTable->getAdapter()->quoteInto('option_id=?', $optionId);
        $pageHasOptionDbTable->delete($where);
    }
}