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
}

