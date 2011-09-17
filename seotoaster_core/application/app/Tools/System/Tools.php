<?php

/**
 * Tools
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_System_Tools {

	public static function getUrlPath($url) {
		$parsedUrl = self::_proccessUrl($url);
		return (isset($parsedUrl['path'])) ? trim($parsedUrl['path'], '/') : '';
	}

	public static function getUrlScheme($url) {
		$parsedUrl = self::_proccessUrl($url);
		return strtolower($parsedUrl['scheme']);
	}

	public static function getUrlHost($url) {
		$parsedUrl = self::_proccessUrl($url);
		return $parsedUrl['host'];
	}

	private static function _proccessUrl($url) {
		$uri = Zend_Uri::factory($url);
		if(!$uri->valid()) {
			throw new Exceptions_SeotoasterException($url . ' is not valid');
		}
		return parse_url($url);
	}

	public static function bobbleSortDeeplinks($deeplinks) {
		$arraySize = count($deeplinks) - 1;
		for($i = $arraySize; $i >= 0; $i--) {
			for($j = 0; $j <= ($i-1); $j++) {
				if(strlen($deeplinks[$j]->getName()) < strlen($deeplinks[$j+1]->getName())) {
					$tmp = $deeplinks[$j];
					$deeplinks[$j] = $deeplinks[$j+1];
					$deeplinks[$j+1] = $tmp;
				}
			}
		}
		return $deeplinks;
	}

	public static function cutExtension($string){
		$exploded = explode('.', $string);
		unset($exploded[sizeof($exploded) - 1]);
		return implode('', $exploded);
	}
}

