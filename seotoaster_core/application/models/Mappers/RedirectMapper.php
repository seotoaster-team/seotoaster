<?php

/**
 * RedirectMapper
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Model_Mappers_RedirectMapper extends Application_Model_Mappers_Abstract {

	protected $_dbTable        = 'Application_Model_DbTable_Redirect';

	protected $_model          = 'Application_Model_Models_Redirect';

	public function save($redirect) {
		if(!$redirect instanceof Application_Model_Models_Redirect) {
			throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_DbTable_Redirect instance');
		}
		$data = array(
			'page_id'     => $redirect->getPageId(),
			'from_url'    => $redirect->getFromUrl(),
			'to_url'      => $redirect->getToUrl(),
			'domain_to'   => $redirect->getDomainTo(),
			'domain_from' => $redirect->getDomainFrom()
		);
		if(null === ($id = $redirect->getId())) {
			unset($data['id']);
			$this->getDbTable()->insert($data);
		}
		else {
			$this->getDbTable()->update($data, array('id = ?' => $id));
		}
	}

	public function fetchRedirectMap() {
		$redirectMap = array();
		$redirects   = $this->fetchAll();
		if(!empty($redirects)) {
			foreach ($redirects as $redirect) {
				$redirectMap[$redirect->getFromUrl()] = $redirect->getToUrl();
			}
		}
		return $redirectMap;
	}

	public function delete(Application_Model_Models_Redirect $redirect) {
		$where = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $redirect->getId());
		$deleteResult = $this->getDbTable()->delete($where);
		$redirect->notifyObservers();
	}

	public function deleteByPageId($pageId) {
		$where = $this->getDbTable()->getAdapter()->quoteInto('page_id', $pageId);
		$this->getDbTable()->delete($where);
	}

	public function deleteByRedirect($fromUrl, $toUrl) {
		$fromUrl = $this->getDbTable()->getAdapter()->quoteInto("from_url = ?", $fromUrl);
		$toUrl   = $this->getDbTable()->getAdapter()->quoteInto("to_url = ?", $toUrl);
		$where = sprintf("%s AND %s", $fromUrl, $toUrl);
		return $this->getDbTable()->delete($where);
	}
}

