<?php

/**
 * Silo
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Model_DbTable_Silo extends Zend_Db_Table_Abstract {

	protected $_dependentTables = array('Application_Model_DbTable_Page');

	protected $_name            = 'silo';
}

