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
			'columns'       => array('fa_id'),
			'refTableClass' => 'Application_Model_DbTable_Featuredarea',
			'refColumns'    => array('id')
		),
		'Page'         => array(
			'columns'       => array('page_id'),
			'refTableClass' => 'Application_Model_DbTable_Page',
			'refColumns'    => array('id')
		)
	);

}

