<?php

/**
 * PageFeaturedarea
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Model_DbTable_PageFeaturedarea extends Zend_Db_Table_Abstract {

	protected $_name = 'page_fa';

	protected $_referenceMap = array(
		'Featuredarea' => array(
			'columns'       => 'fa_id',
			'refTableClass' => 'Application_Model_DbTable_Featuredarea'
		),
	);

}

