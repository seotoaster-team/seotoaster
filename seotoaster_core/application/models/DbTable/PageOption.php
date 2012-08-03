<?php
/**
 * PageOption
 * @author: iamne <eugene@seotoaster.com> Seotoaster core team
 * Date: 7/26/12
 * Time: 5:25 PM
 */
class Application_Model_DbTable_PageOption extends Zend_Db_Table_Abstract {

    protected $_name = 'page_option';

    protected $_dependentTables = array(
        'Application_Model_DbTable_PageHasOption'
    );
}
