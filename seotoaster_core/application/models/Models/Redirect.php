<?php

/**
 * Redirect
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Model_Models_Redirect extends Application_Model_Models_Abstract {

	protected $_pageId     = 0;

	protected $_fromUrl    = '';

	protected $_toUrl      = '';

	protected $_domainTo   = '';

	protected $_domainFrom = '';

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

	public function getDomainTo() {
		return $this->_domainTo;
	}

	public function setDomainTo($domainTo) {
		$this->_domainTo = $domainTo;
		return $this;
	}

	public function getDomainFrom() {
		return $this->_domainFrom;
	}

	public function setDomainFrom($domainFrom) {
		$this->_domainFrom = $domainFrom;
		return $this;
	}
}

