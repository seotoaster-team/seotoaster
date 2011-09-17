<?php

/**
 * UrlScheme
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Filters_UrlScheme implements Zend_Filter_Interface {

	public function filter($value) {
		$exploded        = explode('://', $value);
		if(sizeof($exploded) < 2) {
			return $value;
		}
		return $exploded[sizeof($exploded) - 2] . '://' . end($exploded);
	}

}

