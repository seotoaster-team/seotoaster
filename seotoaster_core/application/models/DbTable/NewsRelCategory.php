<?php

/**
 * NewsRelCategory represents relation table for the news items and news categories
 * 'news_rel_category'
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Model_DbTable_NewsRelCategory extends Zend_Db_Table_Abstract {

	protected $_name = 'news_rel_category';

	protected $_referenceMap = array(
		'News' => array(
			'columns'       => array('news_id'),
			'refTableClass' => 'Application_Model_DbTable_News',
			'refColumns'    => array('id')
		),
		'NewsCategory'      => array(
			'columns'       => array('category_id'),
			'refTableClass' => 'Application_Model_DbTable_NewsCategory',
			'refColumns'    => array('id')
		)
	);

}

