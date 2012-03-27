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

	private $_canonicMap         = array(
		'index.html',
		'index.htm'
	);

	public function init() {
		$this->_cache      = Zend_Controller_Action_HelperBroker::getStaticHelper('Cache');
		$this->_redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector');
		$this->_website    = Zend_Controller_Action_HelperBroker::getStaticHelper('Website');
	}

	public function validate($pageUrl) {
		$pageUrl = (!$pageUrl) ? $this->_website->getDefaultPage() : preg_replace('/\.htm$/', '.html', $pageUrl);
		return $pageUrl;
	}

	public function filterUrl($pageUrl) {
		if(extension_loaded('mbstring')) {
			$pageUrl = mb_eregi_replace('[^\w\d\s_.\-\/]+', '', $this->validate($pageUrl));
			$pageUrl = mb_strtolower($pageUrl, mb_detect_encoding($pageUrl));
		}
		else {
			$pageUrl = preg_replace('~[^\w\d\s_.\-\/]+~ui', '', $pageUrl);
		}
		$pageUrl = trim(preg_replace('~[\s-]+~', '-', $pageUrl), '-');
		if(!preg_match('/\.html$/', $pageUrl)) {
			$pageUrl .= '.html';
		}
		return $pageUrl;
	}

	public function doCanonicalRedirect($pageUrl) {
		$this->_redirector->setCode(301);
              	
        if($_SERVER['HTTP_HOST'] != Tools_System_Tools::getUrlHost($this->_website->getUrl())) {
			$this->_redirector->gotoUrl($this->_website->getUrl() . $pageUrl);
		}

		if(in_array($pageUrl, $this->_canonicMap)) {
			$this->_redirector->gotoUrl($this->_website->getUrl());
		}
	}

	public function do301Redirect($pageUrl) {
		$redirectMap = array();
		$this->_redirector->setCode(301);

		if(!$redirectMap = $this->_cache->load('toaster_301redirects', '301redirects')) {
			$redirectMap = Application_Model_Mappers_RedirectMapper::getInstance()->fetchRedirectMap();
			if(!empty ($redirectMap)) {
				$this->_cache->save('toaster_301redirects', $redirectMap, '301redirects', array(), Helpers_Action_Cache::CACHE_LONG);
			}
		}

		$pageUrl = $this->_website->getUrl() . $pageUrl;

		if(isset($redirectMap[$pageUrl]) && $redirectMap[$pageUrl]) {
			$this->_redirector->gotoUrl($redirectMap[$pageUrl]);
		}
	}

	public function clean($pageUrl) {
		return preg_replace('/\.html$/', '', $pageUrl);
	}

	public function getCanonicMap() {
		return $this->_canonicMap;
	}

}

