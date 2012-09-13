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
				//$content = str_replace('{' . $sub . '}', $replace, $content);
				$content = strtr($content, array('{' . $sub . '}' => $replace));
			}
		}
		return $content;
	}

	/**
	 * Method scan given object for properties which has public getters
	 * and generate array of entities-replacements pairs from this method
	 * @param $object Object
	 * @param $namespace Custom namespace for replacements
	 * @return Tools_Content_EntityParser Return self for chaining
	 * @throws Exceptions_SeotoasterException
	 */
	public function objectToDictionary($object, $namespace = null) {
		if (!is_object($object)) {
			throw new Exceptions_SeotoasterException('Given variable must be an object');
		}
		$reflection = new Zend_Reflection_Class($object);
		$dictionary = array();
		foreach ($reflection->getProperties() as $prop) {
			$normalizedPropName = join('', array_map('ucfirst', explode('_',$prop->getName())));
			$getter = 'get' . join('', array_map('ucfirst', explode('_',$prop->getName())));
			if ($reflection->hasMethod($getter)){
				$replacement = $object->$getter();
				$className = empty($namespace) ? preg_replace('/.*_([\w\d]*)$/','$1', $reflection->getName()) : $namespace;
				$entityName = strtolower($className.':'.$normalizedPropName);
				if (!is_array($replacement) && !is_object($replacement)){
					$dictionary[$entityName] = $replacement;
				}

			}
		}

		$this->addToDictionary($dictionary);

		return $this;
	}

}

