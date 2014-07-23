<?php

class Application_Form_Container extends Zend_Form {

	protected $_content       = '';

	protected $_containerType = '';

	protected $_containerName = '';

	protected $_containerId   = null;

	protected $_pageId        = '';

	protected $_published     = true;

	public function init() {
		$this->setMethod(Zend_Form::METHOD_POST);
		$this->setAttribs(array('id'=> 'frm_content', 'class' => 'grid_12 content-auto'));

		$this->addElement('button', 'submit', array(
			'id'     => 'btn-submit',
            'label' => 'Save content',
            'type'  => 'submit',
			'class'  => 'formsubmit btn ticon-save',
			'ignore' => true,
            'escape'=> false
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
		$this->removeDecorator('DtDdWrapper');
		$this->removeDecorator('DlWrapper');
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

	public function getPublished() {
		return $this->_published;
	}

	public function setPublished($published) {
		$this->_published = $published;
		return $this;
	}


}