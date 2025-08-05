<?php

class Application_Model_Mappers_GalDraggableMapper extends Application_Model_Mappers_Abstract {

	protected $_dbTable = 'Application_Model_DbTable_GalDraggableDbTable';

    protected $_model   = 'Application_Model_Models_GalDraggableModel';

    public function save($model)
    {
        if (!$model instanceof $this->_model) {
            $model = new $this->_model($model);
        }

        $data = array(
            'id'         => $model->getId(),
            'data'       => $model->getData(),
            'updated_at' => $model->getUpdatedAt(),
            'user_id'    => $model->getUserId(),
            'ip_address' => $model->getIpAddress(),
            'page_id'    => $model->getPageId()
        );

        $recordExists = $this->find($data['id']);
        if ($recordExists instanceof Application_Model_Models_GalDraggableModel) {
            $where = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $model->getId());
            $this->getDbTable()->update($data, $where);
        } else {
            $id = $this->getDbTable()->insert($data);
            $model->setId($id);
        }

        return $model;
    }


}