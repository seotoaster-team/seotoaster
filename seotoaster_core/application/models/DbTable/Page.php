<?php

class Application_Model_DbTable_Page extends Zend_Db_Table_Abstract {

	protected $_name = 'page';

	protected $_referenceMap = array(
		'Template' => array(
			'columns'       => 'template_id',
			'refTableClass' => 'Application_Model_DbTable_Template'
		),
		'Silo'     => array(
			'columns'       => 'silo_id',
			'refTableClass' => 'Application_Model_DbTable_Silo'
		)
	);

	protected $_dependentTables = array(
		'Application_Model_DbTable_PageFeaturedarea',
		'Application_Model_DbTable_Optimized',
        'Application_Model_DbTable_PageHasOption'
	);

    public function fetchAllMenu($menuType, $fetchSysPages = false) {
        $where     = $this->getAdapter()->quoteInto("show_in_menu = '?'", $menuType);
        $sysWhere  = $this->getAdapter()->quoteInto("system = '?'", intval($fetchSysPages));
        $where    .= (($where) ? ' AND ' . $sysWhere : $sysWhere);
        $select = $this->getAdapter()->select()
            ->from('page', array(
                'id',
                'navName' => 'nav_name',
                'h1',
                'url',
                //'protected',
                'parentId' => 'parent_id'
            )
        )->joinLeft('optimized', 'page_id = id', array(
            'optimizedUrl' => 'url',
            'optimizedH1'  => 'h1',
            'optimizedNavName'    => 'nav_name'
        ))
        ->where($where)
        ->order(array('order'));

        $pages = $this->getAdapter()->fetchAll($select);

        if(is_array($pages) && !empty($pages)) {
            foreach($pages as $key => $pageData) {
                $pages[$key]['extraOptions'] = $this->_fetchPageOptions($pageData['id']);
            }
        }
        return $pages;
    }

    /**
     * Find page and all data for this page from the optimized table
     *
     * @param integer $id
     * @return Zend_Db_Table_Row
     */
    public function findPage($id) {
        $where = $this->getAdapter()->quoteInto('id=?', $id);
        $select = $this->getAdapter()->select()
            ->from('page')
            ->joinLeft('optimized', 'page_id=id', array(
                'optimizedUrl'               => 'url',
                'optimizedH1'                => 'h1',
                'optimizedHeaderTitle'       => 'header_title',
                'optimizedNavName'           => 'nav_name',
                'optimizedTargetedKeyPhrase' => 'targeted_key_phrase',
                'optimizedMetaDescription'   => 'meta_description',
                'optimizedMetaKeywords'      => 'meta_keywords'
            ))
            ->where($where);
        $data = $this->getAdapter()->fetchRow($select);

        if(!$data) {
            return null;
        }
        return new Zend_Db_Table_Row(array(
            'table' => $this,
            'data'  => array_merge($data, array('extraOptions' => $this->_fetchPageOptions($id)))
        ));
    }

    public function fetchAllPages($where = '', $order = array()) {
        $select = $this->getAdapter()->select()
            ->from('page')
            ->joinLeft('optimized', 'page_id=id', array(
                'optimizedUrl'               => 'url',
                'optimizedH1'                => 'h1',
                'optimizedHeaderTitle'       => 'header_title',
                'optimizedNavName'           => 'nav_name',
                'optimizedTargetedKeyPhrase' => 'targeted_key_phrase',
                'optimizedMetaDescription'   => 'meta_description',
                'optimizedMetaKeywords'      => 'meta_keywords'
            ))
            ->where($where)
            ->order($order);
        $data = $this->getAdapter()->fetchAll($select);
        if(!$data) {
            return null;
        }
        return new Zend_Db_Table_Rowset(array(
            'table'    => $this,
            'rowClass' => $this->getRowClass(),
            'data'     => $this->getAdapter()->fetchAll($select)
        ));
    }

    public function fetchAllByContent($content, $originalsOnly) {
        $where  = $this->getAdapter()->quoteInto('content LIKE ?', '%' . $content . '%');
        if($originalsOnly) {
            $select = $this->getAdapter()->select()->from('page');
        } else {
            $select = $this->_getOptimizedSelect();
        }
        $select->join('container', 'container.page_id=page.id', array())->where($where);
        return $this->getAdapter()->fetchAll($select);
    }

    public function fetchPageOptions($id, $idsOnly = true) {
        return $this->_fetchPageOptions($id, $idsOnly);
    }

    protected function _fetchPageOptions($id, $idsOnly = true) {
        $entries = array();
        $row     =  new Zend_Db_Table_Row(array(
            'table' => $this,
            'data'  => array('id' => $id)
        ));
        $optionsData = $row->findManyToManyRowset('Application_Model_DbTable_PageOption', 'Application_Model_DbTable_PageHasOption')->toArray();
        if($idsOnly) {
            if(empty($optionsData)) {
               return $optionsData;
            }
            foreach($optionsData as $optionData) {
                $entries[] = $optionData['id'];
            }
            return $entries;
        }
        return $optionsData;
    }

    private function _getOptimizedSelect() {
        return $this->getAdapter()->select()
            ->from('page')
            ->joinLeft('optimized', 'page_id=id', array(
            'optimizedUrl'               => 'url',
            'optimizedH1'                => 'h1',
            'optimizedHeaderTitle'       => 'header_title',
            'optimizedNavName'           => 'nav_name',
            'optimizedTargetedKeyPhrase' => 'targeted_key_phrase',
            'optimizedMetaDescription'   => 'meta_description',
            'optimizedMetaKeywords'      => 'meta_keywords'
        ));
    }
}

