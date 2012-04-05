<?php
/**
 * Optimized pages table
 *
 * @author iamne Eugene I. Nezhuta <eugene@seotoaster.com>
 */

class Application_Model_DbTable_Optimized extends Zend_Db_Table_Abstract {

	protected $_name = 'optimized';

	protected $_referenceMap = array(
		'Page' => array(
			'columns'       => 'page_id',
			'refTableClass' => 'Application_Model_DbTable_Page'
		)
	);

}
