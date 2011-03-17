<?php

class Application_Form_Container extends Zend_Form {

	protected $_content       = '';

	protected $_containerType = '';

	protected $_containerName = '';

	protected $_containerId   = null;

	protected $_pageId        = '';

	public function init() {
		$this->setMethod('post');
		$this->setAttrib('id', 'frm_content');
		
		$this->addElement('submit', 'submit', array(
			'label'  => 'Save container',
			'class'  => 'formsubmit',
			'ignore' => true
		));

		$this->addElement('hidden', 'containerType', array(
			'value' => $this->_containerType,
			'id'    => 'container_type'
		));

		$this->addElement('hidden', 'containerName', array(
			'value' => $this->_containerName,
			'id'    => 'container_name'
		));

		$this->addElement('hidden', 'pageId', array(
			'value' => $this->_pageId,
			'id'    => 'page_id'
		));

		$this->addElement('hidden', 'containerId', array(
			'value' => $this->_containerId,
			'id'    => 'container_id'
		));
		
		$this->setElementDecorators(array('ViewHelper', 'Errors'));
    }

	public function getContent() {
		return $this->_content;
	}

	public function setContent($content) {
		$this->_content = $content;
		return $this;
	}

	public function getContainerType() {
		return $this->_containerType;
	}

	public function setContainerType($containerType) {
		$this->_containerType = $containerType;
		return $this;
	}

	public function getContainerName() {
		return $this->_containerName;
	}

	public function setContainerName($name) {
		$this->_containerName = $name;
		return $this;
	}

	public function getPageId() {
		return $this->_pageId;
	}

	public function setPageId($pageId) {
		$this->_pageId = $pageId;
		return $this;
	}

	public function getContainerId() {
		return $this->_containerId;
	}

	public function setContainerId($containerId) {
		$this->_containerId = $containerId;
		return $this;
	}
}