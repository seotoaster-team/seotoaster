<?php

/**
 * Redirect database table
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Model_DbTable_Redirect extends Zend_Db_Table_Abstract {

	protected $_name          = 'redirect';


	//Moved to db layer
	 /*protected $_referenceMap = array(
        'Page' => array(
            'columns'       => array('page_id'),
            'refTableClass' => 'Application_Model_DbTable_Page',
			'refColumns'    => array('id'),
            'onDelete'      => self::CASCADE,
        )
	);*/

}

