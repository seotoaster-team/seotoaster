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
        $this->_readObserversQueue();
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
            $method = 'set' . ucfirst($this->_normalizeOptionsKey($key));
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
		$this->_id = ($id) ? intval($id) : null;
		return $this;
	}

	protected function _normalizeOptionsKey($key) {
		$exploded = explode('_', $key);
		$exploded = array_map('ucfirst', $exploded);
		return join('', $exploded);
	}

	public function toArray() {
		$vars = array();
		$methods = get_class_methods($this);
		$props   = get_class_vars(get_class($this));
        foreach ($props as $key => $value) {
			$method = 'get' . ucfirst($this->_normalizeOptionsKey($key));
            if (in_array($method, $methods)) {
                $vars[str_replace('_', '', $key)] = $this->$method();
            }
        }
        return $vars;
	}

    /**
     * Checking the observer queue. If any, register those observers
	 * 
     */
    protected function _readObserversQueue() {
        static $checked = array();
		$modelClassName = get_called_class();
//        if(!in_array($modelClassName, $checked)) {
			$dbTable   = new Application_Model_DbTable_ObserversQueue();
	        $resultSet = $dbTable->fetchAll($dbTable->getAdapter()->quoteInto('namespace="?"', $modelClassName));
            $checked[] = $modelClassName;
            if($resultSet === null) {
                return;
            }
            foreach($resultSet as $resultRow) {
                $rowArray  = $resultRow->toArray();
                $this->registerObserver(new $rowArray['observer']());
            }
//        }
    }

}

