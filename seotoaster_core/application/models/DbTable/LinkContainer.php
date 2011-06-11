<?php

/**
 * LinkContainer
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Model_DbTable_LinkContainer extends Zend_Db_Table_Abstract {

	protected $_name = 'link_container';

	/* Moved on db side
	 protected $_referenceMap = array(
        'Container' => array(
            'columns'       => array('id_container'),
            'refTableClass' => 'Application_Model_DbTable_Container',
			'refColumns'    => array('id'),
            'onDelete'      => self::CASCADE,
        )
	);
	 */

}

