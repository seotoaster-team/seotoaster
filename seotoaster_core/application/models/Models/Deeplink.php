<?php

/**
 * Deeplink
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Model_Models_Deeplink extends Application_Model_Models_Abstract {

	const TYPE_INTERNAL = 'int';

	const TYPE_EXTERNAL = 'ext';

	protected $_name      = '';

	protected $_url       = '';

	protected $_type      = self::TYPE_INTERNAL;

	protected $_banned    = false;

	protected $_nofollow  = false;

	protected $_pageId    = '';

	public function  __construct(array $options = null) {
		parent::__construct($options);
	}

	public function getName() {
		return $this->_name;
	}

	public function setName($name) {
		$this->_name = $name;
		return $this;
	}

	public function getUrl() {
		return $this->_url;
	}

	public function setUrl($url) {
		$this->_url = $url;
		return $this;
	}

	public function getType() {
		return $this->_type;
	}

	public function setType($type) {
		$this->_type = $type;
		return $this;
	}

	public function getBanned() {
		return $this->_banned;
	}

	public function setBanned($banned) {
		$this->_banned = $banned;
		return $this;
	}

	public function getNofollow() {
		return $this->_nofollow;
	}

	public function setNofollow($nofollow) {
		$this->_nofollow = $nofollow;
		return $this;
	}

	public function getPageId() {
		return $this->_pageId;
	}

	public function setPageId($pageId) {
		$this->_pageId = $pageId;
		return $this;
	}
}

