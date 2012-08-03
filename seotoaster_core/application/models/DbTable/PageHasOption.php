<?php
/**
 * PageHasOption
 * @author: iamne <eugene@seotoaster.com> Seotoaster core team
 * Date: 7/26/12
 * Time: 5:25 PM
 */
class Application_Model_DbTable_PageHasOption extends Zend_Db_Table_Abstract {

    protected $_name = 'page_has_option';

    protected $_referenceMap = array(
        'Page' => array(
            'columns'		=> 'page_id',
            'refTableClass'	=> 'Application_Model_DbTable_Page',
            'refColumns'	=> 'id'
        ),
        'Option' => array(
            'columns'		=> 'option_id',
            'refTableClass'	=> 'Application_Model_DbTable_PageOption',
            'refColumns'	=> 'id'
        )
    );

}
