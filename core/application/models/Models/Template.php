<?php

class Application_Model_Models_Template extends Application_Model_Models_Abstract {

	private $_name         = '';

	private $_content      = '';

	private $_previewImage = '';

	public function getPreviewImage() {
		return $this->_previewImage;
	}

	public function setPreviewImage($previewImage) {
		$this->_previewImage = $previewImage;
		return $this;
	}

	public function getName() {
		return $this->_name;
	}

	public function setName($name) {
		$this->_name = $name;
		return $this;
	}

	public function getContent() {
		return $this->_content;
	}

	public function setContent($content) {
		$this->_content = $content;
		return $this;
	}

	public function setId($id) {
		parent::setId($id);
		return $this;
	}
}

