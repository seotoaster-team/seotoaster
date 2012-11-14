<?php

/**
 * SqlSplitter
 *
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Tools_System_SqlSplitter {

	/**
	 * Split file/string containing multiple sql to an array
	 * @param string String contains sql queries or content of .sql file
	 * @return array List of queries
	 */
	public static function split($sql)
	{
		$sql = trim($sql);
		$sql = preg_replace("/\n\--[^\n]*/", '', "\n".$sql);
		$buffer = array ();
		$result = array ();
		$in_string = false;
        
		for ($i = 0; $i < strlen($sql) - 1; $i ++) {
			if ($sql[$i] == ";" && !$in_string) {
				$result[] = trim(substr($sql, 0, $i));
				$sql = substr($sql, $i +1);
				$i = 0;
			}
			if ($in_string && ($sql[$i] == $in_string) && $buffer[1] != "\\") {
				$in_string = false;
			} elseif (!$in_string && ($sql[$i] == '"' || $sql[$i] == "'") && (!isset ($buffer[0]) || $buffer[0] != "\\")) {
				$in_string = $sql[$i];
			}
			if (isset ($buffer[1]))	{
				$buffer[0] = $buffer[1];
			}
			$buffer[1] = $sql[$i];
		}
		if (!empty ($sql)) {
            if (trim($sql) !== '') {
                $result[] = $sql;
            }
		}
		return $result;
	}

}