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

	private $_containerType = self::TYPE_REGULARCONTENT;

	private $_pageId        = 0;

	private $_name          = '';

	private $_published     = true;

	private $_pubDate       = '';

	private $_content       = '';

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
		return $this->_pubDate;
	}

	public function setPublishingDate($pubDate) {
		$this->_pubDate = ($pubDate && $pubDate !== '0000-00-00') ? date('Y-m-d', strtotime($pubDate)) : '';
		return $this;
	}

	public function getContent() {
		return $this->_content;
	}

	public function setContent($content) {
		$this->_content = $content;
		return $this;
	}

	public function  toArray() {
		$vars = array();
		$methods = get_class_methods($this);
		$props   = get_class_vars(get_class($this));
        foreach ($props as $key => $value) {
			$method = 'get' . ucfirst($this->_normalozeOptionsKey($key));
            if (in_array($method, $methods)) {
                $vars[str_replace('_', '', $key)] = $this->$method();
            }
        }
        return $vars;
	}

}

