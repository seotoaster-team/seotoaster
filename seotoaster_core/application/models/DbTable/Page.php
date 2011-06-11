<?php

class Application_Model_DbTable_Page extends Zend_Db_Table_Abstract {

	protected $_name = 'page';

	protected $_referenceMap = array(
		'Template' => array(
			'columns' => 'template_id',
			'refTableClass' => 'Application_Model_DbTable_Template'
		)
	);
}

