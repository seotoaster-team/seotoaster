<?php

/**
 * Featuredarea
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Model_DbTable_Featuredarea extends Zend_Db_Table_Abstract {

	protected $_name = 'featured_area';

	protected $_dependentTables = array('Application_Model_DbTable_PageFeaturedarea');

}

