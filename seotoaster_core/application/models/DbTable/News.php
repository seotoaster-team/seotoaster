<?php

/**
 * News
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Model_DbTable_News extends Zend_Db_Table_Abstract {

	protected $_name = 'news';

	protected $_referenceMap = array(
		'Page' => array(
			'columns'       => 'page_id',
			'refTableClass' => 'Application_Model_DbTable_Page'
		)
	);

	protected $_dependentTables = array('Application_Model_DbTable_NewsRelCategory');
}

