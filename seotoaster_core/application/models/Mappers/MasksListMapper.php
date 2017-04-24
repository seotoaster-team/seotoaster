<?php

/**
 *
 * @method Application_Model_Mappers_MasksListMapper   getInstance()   getInstance()   Returns an instance of itself
 * @method Zend_Db_Table    getDbTable()    getDbTable()    Returns an instance of DbTable
 */
class Application_Model_Mappers_MasksListMapper extends Application_Model_Mappers_Abstract
{

    protected $_model = 'Application_Model_Models_MaskList';

    protected $_dbTable = 'Application_Model_DbTable_MasksList';

    /**
     * Save masks model to DB
     * @param $model Application_Model_Models_MaskList
     * @return Application_Model_Models_MaskList
     * @throws Exceptions_SeotoasterException
     */
    public function save($model)
    {
        if (!$model instanceof $this->_model) {
            $model = new $this->_model($model);
        }

        $data = array(
            'country_code' => $model->getCountryCode(),
            'mask_type' => $model->getMaskType(),
            'mask_value' => $model->getMaskValue(),
            'full_mask_value' => $model->getFullMaskValue()
        );

        $countryCode = $data['country_code'];
        $maskType = $data['mask_type'];
        $maskModel = $this->findMaskByTypeCountryCode($maskType, $countryCode);

        if ($maskModel instanceof Store_Model_ListMask) {
            $where = $this->getDbTable()->getAdapter()->quoteInto('mask_type = ?', $maskType);
            $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('country_code = ?', $countryCode);
            $this->getDbTable()->update($data, $where);
        } else {
            $this->getDbTable()->insert($data);
        }

        return $model;
    }

    /**
     * Get associative array of masks by country code ISO Alpha-2
     *
     * @param string $maskType mask type
     * @return array
     */
    public function getListOfMasksByType($maskType)
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto('mask_type = ?', $maskType);
        $select = $this->getDbTable()->getAdapter()->select()->from('masks_list',
            array('country_code', 'mask_type', 'mask_value', 'full_mask_value'))->where($where);

        return $this->getDbTable()->getAdapter()->fetchAssoc($select);
    }

    /**
     * Find mask by mask type and country code
     *
     * @param string $maskType mask type
     * @param string $countryCode country code ISO Alpha-2
     * @return null
     */
    public function findMaskByTypeCountryCode($maskType, $countryCode)
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto('mask_type = ?', $maskType);
        $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('country_code = ?', $countryCode);

        return $this->_findWhere($where);
    }

}
