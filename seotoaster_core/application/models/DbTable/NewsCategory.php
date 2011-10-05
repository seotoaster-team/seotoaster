<?php

/**
 * NewsCategory represents 'news_category' table
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Model_DbTable_NewsCategory extends Zend_Db_Table_Abstract {

	protected $_name = 'news_category';

	protected $_dependentTables = array('Application_Model_DbTable_NewsRelCategory');
}

