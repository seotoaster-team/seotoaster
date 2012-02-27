<?php

abstract class Tools_System_Observable implements Interfaces_Observable {

	protected $_observers = array();

	public function registerObserver($observer) {
		$this->_observers[] = $observer;
		return $this;
	}

	public function removeObserver($observer) {
		unset($this->_observers[array_search($observer, $this->_observers)]);
		return $this;
	}

	public function  notifyObservers() {
		foreach($this->_observers as $observer) {
			$observer->notify($this);
		}
	}

	public function setObservers($observers) {
		$this->_observers = $observers;
	}

	public function getObservers() {
		return $this->_observers;
	}
}

