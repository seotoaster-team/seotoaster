<?php
interface Interfaces_Observable {

	public function registerObserver($observer);
	public function removeObserver($observer);
	public function notifyObservers();
}
