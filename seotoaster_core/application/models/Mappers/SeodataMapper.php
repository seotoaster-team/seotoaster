<?php

/**
 * SeodataMapper
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Model_Mappers_SeodataMapper extends Application_Model_Mappers_Abstract {

	protected $_dbTable = 'Application_Model_DbTable_Seodata';

	protected $_model   = 'Application_Model_Models_Seodata';

	public function save($seodata) {
		if(!$seodata instanceof Application_Model_Models_Seodata) {
			throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_DbTable_Seodata instance');
		}
		$data = array(
			'seo_top'    => $seodata->getSeoTop(),
			'seo_bottom' => $seodata->getSeoBottom(),
			'seo_head'   => $seodata->getSeoHead()
		);
		if(null === ($id = $seodata->getId())) {
			unset($data['id']);
			$this->getDbTable()->insert($data);
		}
		else {
			$this->getDbTable()->update($data, array('id = ?' => $id));
		}
	}

	public function delete(Application_Model_Models_Seodata $seodata) {
		$where = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $seodata->getId());
		$deleteResult = $this->getDbTable()->delete($where);
		$seodata->notifyObservers();
	}
}

