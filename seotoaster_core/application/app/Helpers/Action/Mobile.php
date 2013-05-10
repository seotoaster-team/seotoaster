<?php
/**
 * Helpers_Action_Mobile
 * Helper for detecting mobile devices and device features
 *
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */

class Helpers_Action_Mobile extends Zend_Controller_Action_Helper_Abstract {

	/**
	 * @var Mobile_Detect
	 */
	private $_instance;

	public function init(){
		if ($this->_instance === null){
			$this->_instance = new Mobile_Detect();
		}
	}

	public function __call($name, $arguments) {
		if (method_exists($this->_instance, $name)){
			return call_user_func_array(array($this->_instance, $name), $arguments);
		} else {
			return $this->_instance->__call($name, $arguments);
		}
	}


}