<?php

/**
 * FormDownloadFileMapper.php
 *
 *
 * @method Application_Model_Mappers_FormDownloadFileMapper   getInstance()   getInstance()   Returns an instance of itself
 * @method Zend_Db_Table    getDbTable()    getDbTable()    Returns an instance of DbTable
 */
class Application_Model_Mappers_FormDownloadFileMapper extends Application_Model_Mappers_Abstract
{

    protected $_dbTable = 'Application_Model_DbTable_FormDownloadFile';

    protected $_model = 'Application_Model_Models_FormDownloadFile';

    public function save($form)
    {
        if (!$form instanceof Application_Model_Models_FormDownloadFile) {
            throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_Models_FormDownloadFile instance');
        }
        $data = array(
            'page_id' => $form->getPageId(),
            'form_name' => $form->getFormName(),
            'file_folder' => $form->getFileFolder(),
            'file_name' => $form->getFileName(),
        );

        $formName = $form->getFormName();
        $pageId = $form->getPageId();
        $where = $this->getDbTable()->getAdapter()->quoteInto("form_name=?", $formName);
        $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto("page_id=?", $pageId);
        $recordExists = $this->findRecord($formName, $pageId);
        if (!$recordExists instanceof Application_Model_Models_FormDownloadFile) {
            return $this->getDbTable()->insert($data);
        } else {
            return $this->getDbTable()->update($data, $where);
        }
    }

    public function findRecord($formName, $pageId)
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto("form_name=?", $formName);
        $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto("page_id=?", $pageId);
        return $this->_findWhere($where);
    }

}
