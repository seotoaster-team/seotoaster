<?php

/**
 * FormPageConversion mapper
 *
 * @author Seotoaster Dev Team
 */
class Application_Model_Mappers_FormPageConversionMapper extends Application_Model_Mappers_Abstract {

	protected $_dbTable = 'Application_Model_DbTable_FormPageConversion';

	protected $_model   = 'Application_Model_Models_FormPageConversion';

	public function save($form) {
		if(!$form instanceof Application_Model_Models_FormPageConversion) {
			throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_Models_FormPageConversion instance');
		}
		$data = array(
			'page_id'         => $form->getPageId(),
			'form_name'       => $form->getFormName(),
            'conversion_code' => $form->getConversionCode()
		);
        $formName = $form->getFormName();
        $pageId = $form->getPageId();
        $where = $this->getDbTable()->getAdapter()->quoteInto("form_name=?", $formName);
        $where .= ' AND ' .$this->getDbTable()->getAdapter()->quoteInto("page_id=?", $pageId);
        $existConversionCode = $this->getConversionCode($formName, $pageId);
        if(empty($existConversionCode)){
			return $this->getDbTable()->insert($data);
		}
		else {
			return $this->getDbTable()->update($data, $where);
		}
	}
    
    public function getConversionCode($formName, $pageId) {
        $where = $this->getDbTable()->getAdapter()->quoteInto("form_name=?", $formName);
        $where .= ' AND ' .$this->getDbTable()->getAdapter()->quoteInto("page_id=?", $pageId);
        return $this->fetchAll($where);
	}
	        
}
