<?php

/**
 * Centralized garbage collector for cache system
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Cache_GarbageCollector extends Tools_System_GarbageCollector {

	public function clean() {}

	protected function _runOnDefault() {}
	
	protected function _runOnCreate() {}
	protected function _runOnUpdate() {}
	protected function _runOnDelete() {}
}

