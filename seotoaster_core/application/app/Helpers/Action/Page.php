<?php

/**
 * Page helper
 *
 * Takes care of 404 page, 301 redirects, page url validation
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Helpers_Action_Page extends Zend_Controller_Action_Helper_Abstract {

	private $_cache              = null;

	private $_redirector         = null;

	private $_website            = null;

	public function init() {
		$this->_cache      = Zend_Controller_Action_HelperBroker::getStaticHelper('Cache');
		$this->_redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector');
		$this->_website    = Zend_Controller_Action_HelperBroker::getStaticHelper('Website');
	}

	public function validate($pageUrl) {
		if(!$pageUrl) {
			$pageUrl = $this->_website->getDefaultPage();
		}
		if(!preg_match('/\.html$/', $pageUrl)) {
			$pageUrl .= '.html';
		}
		return str_replace(' ', '-', $pageUrl);
	}

	public function do301Redirect($pageUrl) {
		$redirectMap = array();
		$this->_redirector->setCode(301);

		if(!$redirectMap = $this->_cache->load('toaster_301redirects', '301redirects')) {
			$mapper    = new Application_Model_Mappers_RedirectMapper();
			$redirectMap = $mapper->fetchRedirectMap();
			if(!empty ($redirectMap)) {
				$this->_cache->save('toaster_301redirects', $redirectMap, '301redirects', array(), Helpers_Action_Cache::CACHE_LONG);
			}
		}

		if(isset($redirectMap[$pageUrl]) && $redirectMap[$pageUrl]) {
			$this->_redirector->gotoUrl($this->_website->getUrl() . $redirectMap[$pageUrl]);
		}
	}

}

