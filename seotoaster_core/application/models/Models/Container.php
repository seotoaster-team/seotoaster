<?php

/**
 * Container model
 *
 * @author Seotoaster Dev Team
 */
class Application_Model_Models_Container extends Application_Model_Models_Abstract {

	const TYPE_REGULARCONTENT = 1;

	const TYPE_STATICCONTENT  = 2;

	const TYPE_REGULARHEADER  = 3;

	const TYPE_STATICHEADER   = 4;

	const TYPE_CODE           = 5;

    const TYPE_PREPOP         = 6;

    const TYPE_PREPOPSTATIC   = 7;

	protected $_containerType = self::TYPE_REGULARCONTENT;

	protected $_pageId        = 0;

	protected $_name          = '';

	protected $_published     = true;

	protected $_publishingDate       = '';

	protected $_content       = '';

	public function  __construct(array $options = null) {
		parent::__construct($options);
	}

	public function getContainerType() {
		return $this->_containerType;
	}

	public function setContainerType($containerType) {
		$this->_containerType = $containerType;
		return $this;
	}

	public function getPageId() {
		return $this->_pageId;
	}

	public function setPageId($pageId) {
		$this->_pageId = $pageId;
		return $this;
	}

	public function getName() {
		return $this->_name;
	}

	public function setName($name) {
		$this->_name = $name;
		return $this;
	}

	public function getPublished() {
		return $this->_published;
	}

	public function setPublished($published) {
		$this->_published = $published;
		return $this;
	}

	public function getPublishingDate() {
		return $this->_publishingDate;
	}

	public function setPublishingDate($pubDate) {
		$this->_publishingDate = ($pubDate && $pubDate !== '0000-00-00') ? date('Y-m-d', strtotime($pubDate)) : '';
		return $this;
	}

	public function getContent() {
		return $this->_content;
	}

	public function setContent($content) {
		$this->_content = $content;
		return $this;
	}
}

