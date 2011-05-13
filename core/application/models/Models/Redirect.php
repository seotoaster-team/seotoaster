<?php

/**
 * Redirect
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Model_Models_Redirect extends Application_Model_Models_Abstract {

	private $_pageId  = 0;

	private $_fromUrl = '';

	private $_toUrl   = '';

	private $_domain  = '';

	public function getPageId() {
		return $this->_pageId;
	}

	public function setPageId($pageId) {
		$this->_pageId = $pageId;
		return $this;
	}

	public function getFromUrl() {
		return $this->_fromUrl;
	}

	public function setFromUrl($fromUrl) {
		$this->_fromUrl = $fromUrl;
		return $this;
	}

	public function getToUrl() {
		return $this->_toUrl;
	}

	public function setToUrl($toUrl) {
		$this->_toUrl = $toUrl;
		return $this;
	}

	public function getDomain() {
		return $this->_domain;
	}

	public function setDomain($_domain) {
		$this->_domain = $_domain;
		return $this;
	}


}

