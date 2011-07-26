<?php

/**
 * Tools
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_System_Tools {

	public static function getUrlPath($url) {
		$parsedUrl = self::_proccessUrl($url);
		return trim($parsedUrl['path'], '/');
	}

	public static function getUrlScheme($url) {
		$parsedUrl = self::_proccessUrl($url);
		return $parsedUrl['scheme'];
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

//	public static function quickSortDeeplinks($deeplinks, $low, $high) {
//		if(($low + 1) == $high) {
//			return $deeplinks;
//		}
//		$center = ceil(($low + $high) / 2);
//		$i      = $low;
//		$j      = $high;
//		do {
//
//			while (strlen($deeplinks[$i]->getName()) < strlen($deeplinks[$center]->getName())) {
//				$i++;
//			}
//			while (strlen($deeplinks[$j]->getName()) > strlen($deeplinks[$center]->getName())) {
//				$j--;
//			}
//
//			if(strlen($deeplinks[$i]->getName()) > strlen($deeplinks[$j]->getName())) {
//				$temp      = $deeplinks[$i];
//				$deeplinks[$i] = $deeplinks[$j];
//				$deeplinks[$j] = $temp;
//			}
//			$center = ($center == $j) ? $i : $j;
//		} while ($i < $j);
//		if($i < $high) {
//			self::quickSortDeeplinks($deeplinks, $i, $high);
//		}
//		if($j > $low) {
//			self::quickSortDeeplinks($deeplinks, $low, $j);
//		}
//		return $deeplinks;
//	}

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

}

