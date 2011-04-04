<?php

/**
 * Seotoaster Model Abstract class
 *
 * Contain base methods for all of the models
 *
 * @author iamne
 */
abstract class Application_Model_Models_Abstract extends Tools_System_Observable {

	protected $_id = null;

	public function  __construct(array $options = null) {
		if(is_array($options)) {
			$this->setOptions($options);
		}
	}

	public function __set($name, $value) {
        $method = 'set' . ucfirst($name);
        if (('mapper' == $name) || !method_exists($this, $method)) {
            throw new Exception('Invalid property');
        }
        $this->$method($value);
	}

    public function __get($name) {
		$method = 'set' . ucfirst($name);
        if (('mapper' == $name) || !method_exists($this, $method)) {
            throw new Exception('Invalid property');
        }
        return $this->$method();
	}


	public function setOptions(array $options) {
		$methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($this->_normalozeOptionsKey($key));
            if (in_array($method, $methods)) {
                $this->$method($value);
            }
        }
        return $this;
	}

	public function getId() {
		return $this->_id;
	}

	public function setId($id) {
		$this->_id = (int) $id;
		return $this;
	}

	protected function _normalozeOptionsKey($key) {
		$exploded = explode('_', $key);
		$exploded = array_map('ucfirst', $exploded);
		return join('', $exploded);
	}

}

