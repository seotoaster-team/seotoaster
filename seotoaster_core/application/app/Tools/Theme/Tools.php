<?php
/**
 * Theme
 *
 * @author Eugene I. Nezhuta <theneiam@gmail.com>
 * Date: 12/18/12
 * Time: 2:14 AM
 */
class Tools_Theme_Tools {

    public static function dump($table, $query) {
        $dbAdapter = Zend_Registry::get('dbAdapter');
        $result    = $dbAdapter->fetchAll($query);
        $sqlDump   = '';
        if(is_array($result)) {
            $length = sizeof($result);
            foreach($result as $key => $data) {
                if(!$sqlDump) {
                    $sqlDump .= 'INSERT INTO `' . $table . '` (' . join(', ', array_map(function($key) {
                        return '`' . $key . '`';
                    }, array_keys($data))) . ') VALUES ';
                }
                $sqlDump .= '(' . join(', ', array_map(function($value) use($dbAdapter) {
                    return (!$value) ? 'NULL' : $dbAdapter->quote($value);
                }, array_values($data))) . (($key == ($length-1)) ? (');' . PHP_EOL) : '),');
            }
        }
        return $sqlDump;
    }

}
