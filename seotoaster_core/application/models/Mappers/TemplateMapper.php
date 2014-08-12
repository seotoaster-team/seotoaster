<?php

class Application_Model_Mappers_TemplateMapper extends Application_Model_Mappers_Abstract {

	protected $_dbTable = 'Application_Model_DbTable_Template';

	protected $_model   = 'Application_Model_Models_Template';

	protected $_defaultTemplates = array(
		'index', 'default', 'category', 'news', 'news list'
	);

	/**
	 * Method save template instance to db
	 * @param $template Application_Model_Models_Template Template object
	 * @param bool $forceNew
	 * @return mixed
	 * @throws Exceptions_SeotoasterException
	 */
	public function save($template) {
		if(!$template instanceof Application_Model_Models_Template) {
			throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_Models_Template instance');
		}
		$data = array(
			'name'          => $template->getName(),
			'content'       => $template->getContent(),
			'type'          => $template->getType()
		);
		if ( $template->getOldName() === null && null === $this->find($template->getName())) {
			return $this->getDbTable()->insert($data);
		}
		else {
			$whereName = $template->getOldName() === null ? $template->getName() : $template->getOldName();
			$status = $this->getDbTable()->update($data, array('name = ?' => $whereName));
			if ($status && $template->getOldName() != $template->getName()) {
				$pagesTable = new Application_Model_DbTable_Page();
				$updatedPageCount = $pagesTable->update(array('template_id' => $template->getName()), array('template_id = ?' => $template->getOldName()));
			}
			return $status;
		}
	}

	/**
	 * Fetch template by type
	 * @param $type Template type
	 * @return array|null Results
	 */
	public function findByType($type) {
		$where = $this->getDbTable()->getAdapter()->quoteInto("type = ?", $type);
		return $this->fetchAll($where);
	}

	/**
	 * Remove template
	 * @param Application_Model_Models_Template $template
	 * @return mixed
	 */
	public function delete(Application_Model_Models_Template $template) {
		return $this->getDbTable()->delete( array('name = ?' => $template->getName()) );
	}

	/**
	 * Flush all templates except requires
	 * @return mixed
	 */
	public function clearTemplates(){
        return $this->getDbTable()->delete( array('name NOT IN (?)' => $this->_defaultTemplates));
	}

    public function fetchAllTypes() {
        $dbTable = new Application_Model_DbTable_TemplateType();
        return $dbTable->getAdapter()->fetchPairs($dbTable->select()->order('title ASC'));
    }
}

