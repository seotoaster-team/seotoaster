<?php

/**
 * EntityParser
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Content_EntityParser {

	private $_options = array();

	private $_dictionary = array();

	public function __construct($options = array()) {
		$this->_options = $options;
	}

	public function getDictionary() {
		return $this->_dictionary;
	}

	public function setDictionary($dictionary) {
		$this->_dictionary = $dictionary;
		return $this;
	}

	public function addToDictionary(array $dictionary) {
		$this->_dictionary = array_merge($this->_dictionary, $dictionary);
	}

	public function parse($content) {
		if(!empty ($this->_dictionary)) {
			foreach ($this->_dictionary as $sub => $replace) {
				$content = str_replace('{' . $sub . '}', $replace, $content);
			}
		}
		return $content;
	}

}

