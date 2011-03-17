<?php
interface Observable {

	public function registerObserver(Observer $object);
	public function removeObserver(Observer $object);
	public function notifyObservers();
}
