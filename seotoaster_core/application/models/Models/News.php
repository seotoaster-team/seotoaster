<?php

/**
 * News
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Model_Models_News extends Application_Model_Models_Page {

	protected $_categories = array();

	protected $_text       = '';

	protected $_archived   = false;

	protected $_featured   = false;

	public function getText() {
		return $this->_text;
	}

	public function setText($text) {
		$this->_text = $text;
		return $this;
	}

	public function getArchived() {
		return $this->_archived;
	}

	public function setArchived($archived) {
		$this->_archived = $archived;
		return $this;
	}

	public function getFeatured() {
		return $this->_featured;
	}

	public function setFeatured($featured) {
		$this->_featured = $featured;
		return $this;
	}

	public function getCategories() {
		return $this->_categories;
	}

	public function setCategories($categories) {
		$this->_categories = $categories;
		return $this;
	}


}

