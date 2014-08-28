<?php

class Application_Model_Models_Plugin extends Application_Model_Models_Abstract implements Zend_Acl_Resource_Interface {

	const ENABLED             = 'enabled';

	const DISABLED            = 'disabled';

	const INSTALL_FILE_NAME   = 'install.sql';

	const UNINSTALL_FILE_NAME = 'uninstall.sql';

    const DEPENDENCY_FILE_NAME = 'dependency.txt';

	protected $_name    = '';

	protected $_status  = '';

	protected $_tags    = array();

	protected $_preview = '';

	protected $_license = '';

	protected $_version;

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

	public function getTags($asString = false) {
		return ($asString) ? implode(',', $this->_tags) : $this->_tags;
	}

	public function setTags($tags) {
		$this->_tags = (is_string($tags) ? explode(',', $tags) : $tags);
		return $this;
	}

	public function getPreview() {
		return $this->_preview;
	}

	public function setPreview($preview) {
		$this->_preview = $preview;
		return $this;
	}

	public function getLicense() {
		return $this->_license;
	}

	public function setLicense($license) {
		$this->_license = $license;
	}

	public function getVersion() {
		return $this->_version;
	}

	public function setVersion($version) {
		$this->_version = $version;
	}
}

