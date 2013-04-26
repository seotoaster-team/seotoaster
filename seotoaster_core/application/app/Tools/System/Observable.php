<?php

abstract class Tools_System_Observable implements Interfaces_Observable {

	protected $_observers = array();

	public function registerObserver($observer) {
		$this->_observers[] = $observer;
		return $this;
	}

	public function removeObserver($observer) {
        if(!is_array($this->_observers) || empty($this->_observers)) {
            return $this;
        }
        $this->_observers = array_filter($this->_observers, function($currentObserver) use($observer) {
            return !$currentObserver instanceof $observer;
        });

		return $this;
	}

	public function  notifyObservers() {
		foreach($this->_observers as $observer) {
			$observer->notify($this);
		}
	}
}

