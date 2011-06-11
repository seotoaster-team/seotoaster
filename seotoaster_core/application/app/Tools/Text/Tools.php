<?php

class Tools_Text_Tools {

	public static function lcFirst($string) {
		$firstChar      = substr($string, 0, 1);
		$firstCharLower = strtolower($firstChar);
		return preg_replace('~^' . $firstChar . '~u', $firstCharLower, $string, 1);
	}

}

