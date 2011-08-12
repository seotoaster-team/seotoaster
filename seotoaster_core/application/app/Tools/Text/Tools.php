<?php

class Tools_Text_Tools {

	public static function lcFirst($string) {
		$firstChar      = substr($string, 0, 1);
		$firstCharLower = strtolower($firstChar);
		return preg_replace('~^' . $firstChar . '~u', $firstCharLower, $string, 1);
	}

	public static function cutText($text, $limit = 0, $startFrom = 0) {
		if(!$limit) {
			return $text;
		}
		if(extension_loaded('mbstring')) {
			mb_internal_encoding('UTF-8');

			if(mb_strlen($text) <= $limit){
				return $text;
			}
			return mb_substr($text, $startFrom, $limit, mb_detect_encoding($text)) . '...';
		}
		else {
			error_log('[seotoaster]: Mbstring extension not loaded');
			if (strlen($text) <= $limit){
				return $text;
			}
			return substr($text, $startFrom, $limit) . '...';
		}
	}

}

