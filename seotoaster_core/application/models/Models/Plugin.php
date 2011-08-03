<?php

class Application_Model_Models_Plugin extends Application_Model_Models_Abstract implements Zend_Acl_Resource_Interface {

	const ENABLED             = 'enabled';

	const DISABLED            = 'disabled';

	const INSTALL_FILE_NAME   = 'install.sql';

	const UNINSTALL_FILE_NAME = 'uninstall.sql';

	private $_name    = '';

	private $_status  = '';

	private $_cache   = false;

	private $_tag     = '';

	private $_preview = '';

	public function getResourceId() {
		return Tools_Security_Acl::RESOURCE_PLUGINS;
	}

	public function getName() {
		return $this->_name;
	}

	public function setName($name) {
		$this->_name = $name;
		return $this;
	}

	public function getStatus() {
		return $this->_status;
	}

	public function setStatus($status) {
		$this->_status = $status;
		return $this;
	}

	public function getCache() {
		return $this->_cache;
	}

	public function setCache($cache) {
		$this->_cache = $cache;
		return $this;
	}

	public function getTag() {
		return $this->_tag;
	}

	public function setTag($tag) {
		$this->_tag = $tag;
		return $this;
	}

	public function getPreview() {
		return $this->_preview;
	}

	public function setPreview($preview) {
		$this->_preview = $preview;
		return $this;
	}


}

