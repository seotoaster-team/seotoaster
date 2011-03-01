<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Tools
 *
 * @author iamne
 */
class Tools_Text_Tools {

	public static function lcFirst($string) {
		$firstChar      = substr($string, 0, 1);
		$firstCharLower = strtolower($firstChar);
		return preg_replace('~' . $firstChar . '~U', $firstCharLower, $string, 1);
	}

}

