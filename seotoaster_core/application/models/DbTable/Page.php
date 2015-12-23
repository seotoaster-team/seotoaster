<?php

class Application_Model_DbTable_Page extends Zend_Db_Table_Abstract {

	protected $_name = 'page';

	protected $_referenceMap = array(
		'Template' => array(
			'columns'       => 'template_id',
			'refTableClass' => 'Application_Model_DbTable_Template'
		),
		'Silo' => array(
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
        $where    = $this->getAdapter()->quoteInto("show_in_menu = '?'", $menuType);
        $sysWhere = $this->getAdapter()->quoteInto("system = '?'", intval($fetchSysPages));
        $select   = $this->getAdapter()->select()
            ->from('page', array('id', 'parentId' => 'parent_id', 'protected', 'external_link_status', 'external_link'))
            ->joinLeft('optimized', 'page_id = id', null)
            ->columns(array(
                'url'          => new Zend_Db_Expr('COALESCE(optimized.url, page.url)'),
                'h1'           => new Zend_Db_Expr('COALESCE(optimized.h1, page.h1)'),
                'navName'      => new Zend_Db_Expr('COALESCE(optimized.nav_name, page.nav_name)'),
                'teaser'       => new Zend_Db_Expr('COALESCE(optimized.teaser_text, page.teaser_text)'),
                'optimized'    => new Zend_Db_Expr('COALESCE(optimized.url, optimized.h1, optimized.header_title, optimized.nav_name, optimized.targeted_key_phrase, optimized.meta_description, optimized.meta_keywords, optimized.teaser_text, NULL)')
            ))
            ->where($sysWhere)
            ->where($where)
            ->order(array('order'));

	    if ($menuType === Application_Model_Models_Page::IN_MAINMENU){
            $subSelect = $this->getAdapter()->select()
                ->distinct()->from('page', 'id')
                ->where("parent_id = '?'", Application_Model_Models_Page::IDCATEGORY_CATEGORY)
                ->where($sysWhere)
                ->where($where)->__toString();
            $select->where("parent_id = '?' OR parent_id IN (".$subSelect.")", Application_Model_Models_Page::IDCATEGORY_CATEGORY);
        }

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
    public function findPage($id, $originalsOnly = false) {
        $where  = $this->getAdapter()->quoteInto('id=?', $id);
        $select = $this->_getOptimizedSelect($originalsOnly)->where($where);
        $data   = $this->getAdapter()->fetchRow($select);

        if(!$data) {
            return null;
        }
        return new Zend_Db_Table_Row(array(
            'table' => $this,
            'data'  => array_merge($data, array('extraOptions' => $this->_fetchPageOptions($id)))
        ));
    }

    public function fetchAllPages($where = '', $order = array(), $originalsOnly = false) {
        $select = $this->_getOptimizedSelect($originalsOnly)
            ->where($where)
            ->order($order);

        $data = $this->getAdapter()->fetchAll($select);
        if(!$data) {
            return null;
        }
        return new Zend_Db_Table_Rowset(array(
            'table'    => $this,
            'rowClass' => $this->getRowClass(),
            'data'     => $data
        ));
    }

    public function findByUrl($pageUrl = Helpers_Action_Website::DEFAULT_PAGE) {
        $where      = $this->getAdapter()->quoteInto('page.url = ?', $pageUrl);
        $orWhere    = $this->getAdapter()->quoteInto('optimized.url = ?', $pageUrl);
        $select     = $this->_getOptimizedSelect(false, array('id', 'parent_id', 'template_id', 'last_update', 'silo_id', 'protected', 'system', 'news', 'preview_image', 'original_url' => 'page.url'));

        $select->join('template', 'page.template_id=template.name', null)
            ->columns(array(
                'content' => 'template.content'
            ))
            ->where($where)
            ->orWhere($orWhere);

        $row = $this->getAdapter()->fetchRow($select);

        if(empty($row)) {
            return null;
        }

        if($pageUrl != Helpers_Action_Website::DEFAULT_PAGE && $pageUrl != $row['url'] && $pageUrl != $row['original_url']) {
            return null;
        }

        // select containers for the current page (including static)
        $select = $this->getAdapter()->select()->from('container', array(
            'uniqHash' => new Zend_Db_Expr("MD5(CONCAT_WS('-',`name`, COALESCE(`page_id`, 0), `container_type`))"),
            'id',
            'name',
            'page_id',
            'container_type',
            'content',
            'published',
            'publishing_date'
        ))
        ->where('page_id = ?', $row['id'])
        ->orWhere('page_id IS NULL');
        $row['containers'] = $this->getAdapter()->fetchAssoc($select);
        return $row;
    }

    public function fetchAllByContent($content, $originalsOnly) {
        $where  = $this->getAdapter()->quoteInto('content LIKE ?', '%' . $content . '%');
        $select = $this->_getOptimizedSelect($originalsOnly);
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

    private function _getOptimizedSelect($originalsOnly, $pageFields = array()) {
        if(empty($pageFields)) {
            $pageFields = array('id', 'template_id', 'parent_id', 'last_update', 'is_404page', 'show_in_menu', 'order', 'weight', 'silo_id', 'protected', 'system', 'draft', 'publish_at', 'news', 'err_login_landing', 'mem_landing', 'signup_landing', 'preview_image', 'external_link_status', 'external_link', 'page_type');
        }
        $select = $this->getAdapter()->select();
        if($originalsOnly) {
            $pageFields = array_merge(array(
                'url'                 => 'page.url',
                'h1'                  => 'page.h1',
                'header_title'        => 'page.header_title',
                'nav_name'            => 'page.nav_name',
                'targeted_key_phrase' => 'page.targeted_key_phrase',
                'meta_description'    => 'page.meta_description',
                'meta_keywords'       => 'page.meta_keywords',
                'teaser_text'         => 'page.teaser_text',
                'optimized'           => 0
            ), $pageFields);
        }
        $select->from('page', $pageFields);
        return ($originalsOnly) ? $select : $select
            ->joinLeft('optimized', 'page_id=id', null)
            ->columns(array(
                'url'                 => new Zend_Db_Expr('COALESCE(optimized.url, page.url)'),
                'h1'                  => new Zend_Db_Expr('COALESCE(optimized.h1, page.h1)'),
                'header_title'        => new Zend_Db_Expr('COALESCE(optimized.header_title, page.header_title)'),
                'nav_name'            => new Zend_Db_Expr('COALESCE(optimized.nav_name, page.nav_name)'),
                'targeted_key_phrase' => new Zend_Db_Expr('COALESCE(optimized.targeted_key_phrase, page.targeted_key_phrase)'),
                'meta_description'    => new Zend_Db_Expr('COALESCE(optimized.meta_description, page.meta_description)'),
                'meta_keywords'       => new Zend_Db_Expr('COALESCE(optimized.meta_keywords, page.meta_keywords)'),
                'teaser_text'         => new Zend_Db_Expr('COALESCE(optimized.teaser_text, page.teaser_text)'),
                'optimized'         => new Zend_Db_Expr('COALESCE(optimized.url, optimized.h1, optimized.header_title, optimized.nav_name, optimized.targeted_key_phrase, optimized.meta_description, optimized.meta_keywords, optimized.teaser_text, NULL)')
            ));
    }
}

