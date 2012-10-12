<?php

class Application_Model_DbTable_Template extends Zend_Db_Table_Abstract {

	protected $_name = 'template';

	protected $_dependentTables = array('Application_Model_DbTable_Page');

}

