<?php
/**
 * Class Application_Model_Mappers_PageMapper
 * @method static Application_Model_Mappers_PageMapper getInstance() getInstance() Returns an instance of itself
 * @method Application_Model_DbTable_Page getDbTable() Returns an instance of corresponding DbTable
 */
class Application_Model_Mappers_PageMapper extends Application_Model_Mappers_Abstract
{

    protected $_dbTable = 'Application_Model_DbTable_Page';

    protected $_model = 'Application_Model_Models_Page';

    protected $_optimized = false;

    protected $_originalsOnly = false;

    protected $_optimizedFields = array(
        'h1',
        'header_title',
        'url',
        'nav_name',
        'meta_description',
        'meta_keywords',
        'targeted_key_phrase',
        'teaser_text'
    );

    /**
     * @param Application_Model_Models_Page $page
     * @return mixed
     * @throws Exceptions_SeotoasterException
     */
    public function save($page)
    {
        if (!$page instanceof Application_Model_Models_Page) {
            throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_Models_Page instance');
        }
        $data = array(
            'template_id'         => $page->getTemplateId(),
            'parent_id'           => $page->getParentId(),
            'h1'                  => $page->getH1(),
            'header_title'        => $page->getHeaderTitle(),
            'url'                 => $page->getUrl(),
            'nav_name'            => $page->getNavName(),
            'meta_description'    => $page->getMetaDescription(),
            'meta_keywords'       => $page->getMetaKeywords(),
            'teaser_text'         => $page->getTeaserText(),
            'show_in_menu'        => $page->getShowInMenu(),
            'order'               => $page->getOrder(),
            'silo_id'             => $page->getSiloId(),
            'targeted_key_phrase' => $page->getTargetedKeyPhrase(),
            'system'              => intval($page->getSystem()),
            'draft'               => intval($page->getDraft()),
            'news'                => intval($page->getNews()),
            'publish_at'          => (!$page->getPublishAt()) ? null : date('Y-m-d', strtotime($page->getPublishAt())),
            'preview_image'       => $page->getPreviewImage(),
            'external_link_status' => $page->getExternalLinkStatus(),
            'external_link'       => $page->getExternalLink(),
            'page_type'           => $page->getPageType(),
            'page_folder'         => $page->getPageFolder(),
            'is_folder_index'         => $page->getisFolderIndex(),
            'exclude_category'    => $page->getExcludeCategory(),
            'page_target_blank'    => $page->getPageTargetBlank(),
            'not_clickable'    => $page->getNotClickable(),
        );


        if ($page->getId()) {
            $this->getDbTable()->update($data, array('id = ?' => $page->getId()));
        } else {
            $pageId = $this->getDbTable()->insert($data);
            $page->setId($pageId);
        }

        //save page options
        $options = $page->getExtraOptions();
        $pageHasOptionTable = new Application_Model_DbTable_PageHasOption();
        if (!empty($options)) {
            $pageHasOptionTable->getAdapter()->beginTransaction();
            $pageHasOptionTable->delete($pageHasOptionTable->getAdapter()->quoteInto('page_id = ?', $page->getId()));
            foreach ($options as $option) {
                $pageHasOptionTable->insert(
                    array(
                        'page_id'   => $page->getId(),
                        'option_id' => $option
                    )
                );
            }
            $pageHasOptionTable->getAdapter()->commit();
        } else {
            $pageHasOptionTable->delete($pageHasOptionTable->getAdapter()->quoteInto('page_id = ?', $page->getId()));
        }

        return $page;
    }

    public function fetchAll($where = '', $order = array(), $fetchSysPages = false, $originalsOnly = false, $limit = null, $offset = null)
    {
        $dbTable = $this->getDbTable();

        //exclude system pages from select
        $sysWhere = $dbTable->getAdapter()->quoteInto("system = '?'", intval($fetchSysPages));
        $where .= (($where) ? ' AND ' . $sysWhere : $sysWhere);
        $order[] = 'order';
        $entries = array();
        $resultSet = $dbTable->fetchAllPages($where, $order, $originalsOnly, $limit, $offset);

        if (null === $resultSet) {
            return null;
        }

        $this->_originalsOnly = $originalsOnly;
        if (!$resultSet || empty($resultSet)) {
            return null;
        }

        /*foreach($resultSet as $row) {
            $row       = array_merge(array('extraOptions' => $this->getDbTable()->fetchPageOptions($row->id)), $row->toArray());
            $entries[] = $this->_toModel($row, $originalsOnly);
        }*/

        $model = $this->_model;
        $entries = array_map(
            function ($row) use (&$dbTable, $model, &$originalsOnly) {
                $row = array_merge($row, array('extraOptions' => $dbTable->fetchPageOptions($row['id'])));
                return new $model($row);
            },
            $resultSet->toArray()
        );

        return $entries;
    }

    /**
     * Fetch pages by given option
     *
     * @param string $option
     * @param bool   $firstOccurrenceOnly If true returns only first element of the result array
     * @return array|null
     */
    public function fetchByOption($option, $firstOccurrenceOnly = false)
    {
        $entries = array();
        $optionTable = new Application_Model_DbTable_PageOption();
        $optionRowset = $optionTable->find($option);
        if (!$optionRowset) {
            return null;
        }
        $optionRow = $optionRowset->current();
        if (!$optionRow) {
            return null;
        }
        $pagesRowset = $optionRow->findManyToManyRowset(
            'Application_Model_DbTable_Page',
            'Application_Model_DbTable_PageHasOption'
        );
        foreach ($pagesRowset as $pageRow) {
            $templateRow = $pageRow->findParentRow('Application_Model_DbTable_Template');
            $pageRow = $pageRow->toArray();
            $pageRow['content'] = ($templateRow !== null) ? $templateRow->content : '';
            $pageRow['extraOptions'] = Application_Model_Mappers_PageMapper::getInstance()->getDbTable(
            )->fetchPageOptions($pageRow['id']);
            $select = $this->getDbTable()->getAdapter()->select()->from(
                'container',
                array(
                    'uniqHash' => new Zend_Db_Expr("MD5(CONCAT_WS('-',`name`, COALESCE(`page_id`, 0), `container_type`))"),
                    'id',
                    'name',
                    'page_id',
                    'container_type',
                    'content',
                    'published',
                    'publishing_date'
                )
            )->where('page_id IS NULL OR page_id = ?', $pageRow['id']);
            $pageRow['containers'] = $this->getDbTable()->getAdapter()->fetchAssoc($select);
            $entries[] = $this->_toModel($pageRow);
        }
        if ($firstOccurrenceOnly) {
            return (isset($entries[0])) ? $entries[0] : null;
        }
        return $entries;
    }

    public function fetchAllUrls()
    {
        $select = $this->getDbTable()->select(Zend_Db_Table::SELECT_WITHOUT_FROM_PART)
            ->from($this->getDbTable()->info('name'), array('url'));
        return $this->getDbTable()->getAdapter()->fetchCol($select);
    }

    public function fetchAllStaticMenuPages()
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto(
            "show_in_menu = '?'",
            Application_Model_Models_Page::IN_STATICMENU
        );
        return $this->fetchAll($where);
    }

    public function fetchAllMainMenuPages()
    {
        return $this->getDbTable()->fetchAllMenu(Application_Model_Models_Page::IN_MAINMENU);
    }

    public function fetchAllDraftPages()
    {
        return $this->fetchAll("draft = '1'", array(), true);
    }

    public function getDraftPagesCount()
    {
        $table = $this->getDbTable();
        $select = $table->select()
            ->from($table, array('count' => new Zend_Db_Expr('COUNT(draft)')))
            ->where('draft = ?', '1')
            ->where('`system` = ?', '1');

        return $table->getAdapter()->fetchOne($select);
    }

    public function fetchAllNomenuPages($pageTypes = array())
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto('show_in_menu = ?', (string) Application_Model_Models_Page::IN_NOMENU);
        $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('parent_id = ?', (string) Application_Model_Models_Page::IDCATEGORY_DEFAULT);
        if (!empty($pageTypes)) {
            $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('page_type IN (?)', $pageTypes);
        }
        return $this->fetchAll($where);
    }


    public function fetchAllNomenuPagesArray($pageTypes = array(), $fetchSysPages = false, $fetchProtected = false)
    {
        $table = $this->getDbTable();
        $select = $table->select()
            ->from(array('p' => 'page'), null)
            ->joinLeft(array('o' => 'optimized'), 'p.id = o.page_id', 'p.id')
            ->columns(
                array(
                    'nav_name' => new Zend_Db_Expr('COALESCE(o.nav_name, p.nav_name)'),
                    'h1' => new Zend_Db_Expr('COALESCE(o.h1, p.h1)'),
                    'url' => new Zend_Db_Expr('COALESCE(o.url, p.url)'),
                    'external_link_status', 'external_link',
                )
            )
            ->where('p.show_in_menu = ?', (string) Application_Model_Models_Page::IN_NOMENU)
            ->where('p.parent_id = ?', (string) Application_Model_Models_Page::IDCATEGORY_DEFAULT);
        if (!$fetchSysPages) {
            $select->where('p.protected = ?', '0');
        }
        if (!$fetchProtected) {
            $select->where('p.system = ?', '0');
        }
        if (!empty($pageTypes)) {
            $select->where('p.page_type IN (?)', $pageTypes);
        }

        return $table->getAdapter()->fetchAll($select);
    }

    public function findByUrl($pageUrl)
    {
        if (!$pageUrl) {
            $pageUrl = Helpers_Action_Website::DEFAULT_PAGE;
        }
        $entry = $this->getDbTable()->findByUrl($pageUrl);

        if (!$entry) {
            return null;
        }

        $entry = array_merge($entry, array('extraOptions' => $this->getDbTable()->fetchPageOptions($entry['id'])));
        return new $this->_model($entry);
    }

    public function findErrorLoginLanding()
    {
        return $this->fetchByOption(Application_Model_Models_Page::OPT_ERRLAND, true);
    }

    public function findMemberLanding()
    {
        return $this->fetchByOption(Application_Model_Models_Page::OPT_MEMLAND, true);
    }

    public function findSignupLandign()
    {
        return $this->fetchByOption(Application_Model_Models_Page::OPT_SIGNUPLAND, true);
    }

    public function findByNavName($navName)
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto('nav_name = ?', $navName);
        return $this->_findWhere($where);
    }

    /**
     * Get pages by parent id
     *
     * @param int $parentId parent id
     * @param bool $draft flag for draft page
     * @param array $pageTypes page types ids array
     * @return array|null
     */
    public function findByParentId($parentId, $draft = false, $pageTypes = array())
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto('parent_id = ?', $parentId);
        if ($draft) {
            $where .= ' OR ' . $this->getDbTable()->getAdapter()->quoteInto('draft = ?', '1');
        }
        if (!empty($pageTypes)) {
            $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('page_type IN (?)', $pageTypes);
        }
        return $this->fetchAll($where);
    }

    public function fetchMainCategories()
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto('parent_id = ?', '0');
        $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('draft = ?', '0');
        $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('show_in_menu = ?', '1');

        return $this->fetchAll($where);
    }


    public function selectCategoriesIdName($useNavName = false)
    {
        $result = array();
        $categories = $this->findByParentId(0, true);
        $translator = Zend_Registry::get('Zend_Translate');
        if (empty($categories)) {
            return array();
        }
        foreach ($categories as $key => $category) {
            if ($useNavName) {
                $categoryName = ($category->getProtected()) ? ($category->getNavName() . '*') : $category->getNavName();
            } else {
                $categoryName = ($category->getProtected()) ? ($category->getH1() . '*') : $category->getH1();
            }
            if ($category->draft == 1) {
                $categoryName .= ' ('.$translator->translate('draft').')';
            }
            $result[$category->getId()] = $categoryName;
        }
        return $result;
    }

    public function find404Page()
    {
        return $this->fetchByOption(Application_Model_Models_Page::OPT_404PAGE, true);
    }

    public function delete(Application_Model_Models_Page $page)
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $page->getId());
        $deleteResult = $this->getDbTable()->delete($where);
        $page->notifyObservers();
        return $deleteResult;
    }

    public function fetchIdUrlPairs($pageTypes = array())
    {
        $select = $this->getDbTable()->select(Zend_Db_Table::SELECT_WITHOUT_FROM_PART)
            ->from($this->getDbTable()->info('name'),
                [
                    'id',
                    "url" => new Zend_Db_Expr("IF(page_folder IS NOT null, IF(is_folder_index = '1', CONCAT(page_folder, '/'), CONCAT(page_folder, '/', url)), url)")
                ]
            )
            ->order('url');


        if (!empty($pageTypes)) {
            $select->where('page_type IN (?)', $pageTypes);
        }

        return $this->getDbTable()->getAdapter()->fetchPairs($select);
    }

    public function fetchIdUrlOptimized($pageTypes = array())
    {
        $table = $this->getDbTable();
        $select = $table->select()
            ->from(array('p' => 'page'), array('id',
                'url' => new Zend_Db_Expr('COALESCE(o.url, p.url)')))
            ->joinLeft(array('o' => 'optimized'), 'p.id = o.page_id', 'p.id')
            ->where('p.protected = ?', '0')
            ->where('p.system = ?', '0');

        if (!empty($pageTypes)) {
            $select->where('p.page_type IN (?)', $pageTypes);
        }
        $select->order('url');
        return $table->getAdapter()->fetchPairs($select);
    }

    protected function  _findWhere($where, $fetchSysPages = false)
    {
        $whereExploded = explode('=', $where);
        $spot = strpos($whereExploded[0], '.');
        if ($spot === false) {
            $whereExploded[0] = str_replace(substr($whereExploded[0], 0, $spot), '', $whereExploded[0]);
        }
        $where = implode('=', $whereExploded);
        $where = '(page.' . $where . ' OR optimized.' . $where . ')';

        $sysWhere = $this->getDbTable()->getAdapter()->quoteInto("system = '?'", intval($fetchSysPages));
        $where .= (($where) ? ' AND ' . $sysWhere : $sysWhere);

        $row = $this->getDbTable()->fetchAllPages($where);

        if (!$row) {
            return null;
        }

        if ($row instanceof Zend_Db_Table_Rowset) {
            $row = $row->current();
        }

        $rowTemplate = $row->findParentRow('Application_Model_DbTable_Template');
        $row = $row->toArray();
        $row['content'] = ($rowTemplate !== null) ? $rowTemplate->content : '';

        //set an extra options for the page
        $row['extraOptions'] = $this->getDbTable()->fetchPageOptions($row['id']);

        unset($rowTemplate);
        return $this->_toModel($row);
    }

    public function find($id, $originalsOnly = false)
    {
        if (!is_array($id)) {
            return $this->_findPage($id, $originalsOnly);
        }
        $pages = array();
        foreach ($id as $pageId) {
            if (null !== ($page = $this->_findPage($pageId, $originalsOnly))) {
                $pages[] = $page;
            }
        }
        return $pages;
    }

    public function fetchAllByContent($content, $originalsOnly = false)
    {
        $pages = $this->getDbTable()->fetchAllByContent($content, $originalsOnly);
        if (!$pages || empty($pages)) {
            return null;
        }
        return array_map(
            function ($pageData) {
                return new Application_Model_Models_Page($pageData);
            },
            $pages
        );
    }

    protected function _findPage($id, $originalsOnly)
    {
        $row = $this->getDbTable()->findPage(intval($id), $originalsOnly);
        if (null == $row) {
            return null;
        }
        return $this->_toModel($row, $originalsOnly);
    }

    protected function _toModel($row, $originalsOnly = false)
    {
        if ($row instanceof Zend_Db_Table_Row) {
            $row = $row->toArray();
        }
        return new $this->_model($row);
    }

    private function _isOptimized($row)
    {
        if ($row instanceof Zend_Db_Table_Row) {
            $row = $row->toArray();
        }
        $isOptimized = false;
        foreach ($row as $key => $value) {
            if (false !== (strpos($key, 'optimized', 0))) {
                $isOptimized = $isOptimized || (boolean)$value;
            }
        }
        return $isOptimized;
    }

    public function getPagesForSearchIndex($limit = null, $offset = null)
    {
        $select = $this->getDbTable()->getAdapter()->select()
                ->from(array('p' => 'page'), null)
                ->joinLeft(array('o' => 'optimized'), 'p.id = o.page_id', null)
                ->joinLeft(array('c' => 'container'), 'p.id = c.page_id AND c.container_type = 1', null)
                ->columns(
                    array(
                        'id'              => 'p.id',
                        'previewImage'    => 'p.preview_image',
                        'url'             => new Zend_Db_Expr('COALESCE(o.url, p.url)'),
                        'h1'              => new Zend_Db_Expr('COALESCE(o.h1, p.h1)'),
                        'navName'         => new Zend_Db_Expr('COALESCE(o.nav_name, p.nav_name)'),
                        'headerTitle'     => new Zend_Db_Expr('COALESCE(o.header_title, p.header_title)'),
                        'metaKeywords'    => new Zend_Db_Expr('COALESCE(o.meta_keywords, p.meta_keywords)'),
                        'metaDescription' => new Zend_Db_Expr('COALESCE(o.meta_description, p.meta_description)'),
                        'teaserText'      => new Zend_Db_Expr('COALESCE(o.teaser_text, p.teaser_text)'),
                        'content'         => new Zend_Db_Expr('GROUP_CONCAT(c.content)')
                    )
                )
                ->where("p.system = '?'", 0)
                ->group('p.id');

        if (!is_null($offset) && is_numeric($offset)) {
            $offset = intval($offset);
        }

        if (!is_null($limit) && is_numeric($limit)) {
            $select->limit(intval($limit), $offset);
        }

        return $this->getDbTable()->getAdapter()->fetchAll($select);

    }

    public function isDraftCategory($id)
    {
        $query = $this->getDbTable()->getAdapter()->select()->from($this->getDbTable()->info('name'),
            array('draft'))->where('id =?', $id);
        $col = $this->getDbTable()->getAdapter()->fetchCol($query);
        if (current($col) == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function publishChildPages($parentId)
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto("parent_id =?", $parentId);
        return $this->getDbTable()->update(array('draft'=>0, 'system'=>0), $where);
    }

    /**
     * return key pair page_type_id page_type_name
     */
    public function getPageTypes()
    {
        $select = $this->getDbTable()->getAdapter()->select()
            ->from('page_types', array('page_type_id', 'page_type_name'));

        return $this->getDbTable()->getAdapter()->fetchPairs($select);
    }

    /**
     * Get page types for resource
     *
     * @param string $resource
     * @return array
     */
    public function getPageTypeByResource($resource)
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto('resource_type = ?', $resource);
        $select = $this->getDbTable()->getAdapter()->select()
            ->from('page_types_access', array('page_type_id', 'resource_type'))->where($where);

        return array_keys($this->getDbTable()->getAdapter()->fetchPairs($select));
    }

    /**
     * This method delete pages by ids
     * @param array $ids
     * @return int  - number of deleted pages
     */
    public function deleteIn($ids = array())
    {
        if(empty($ids)) {
            return false;
        }
        $where = $this->getDbTable()->getAdapter()->quoteInto("id IN (?)", $ids);
        return $this->getDbTable()->delete($where);
    }


    /**
     * return key pair id folder_name
     */
    public function getFolders()
    {
        $select = $this->getDbTable()->getAdapter()->select()
            ->from('page_folder', array('id', 'name'));

        return $this->getDbTable()->getAdapter()->fetchPairs($select);
    }

    public function fetchRegularPagesIdUrlPairs()
    {
        $select = $this->getDbTable()->select(Zend_Db_Table::SELECT_WITHOUT_FROM_PART)
            ->from($this->getDbTable()->info('name'), ['id', 'url'])
            ->where('page_type = 1 AND id NOT IN (SELECT page_id from page_has_option)')
            ->order('url');

        return $this->getDbTable()->getAdapter()->fetchPairs($select);
    }

    public function removeSubfolderInfo($folderName) {
        $where = $this->getDbTable()->getAdapter()->quoteInto('page_folder = ?', $folderName);
        $this->getDbTable()->update(['page_folder' => null, 'is_folder_index' => 0], $where);
    }

    public function findPagesByPageFolderName($pageFolder) {
        $where = $this->getDbTable()->getAdapter()->quoteInto('page.page_folder IN (?)', $pageFolder);
        $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('is_folder_index <> ?', '1');
        $select = $this->getDbTable()->getAdapter()->select()->from('page', array('id', 'page_folder'))->where($where);

        $data = $this->getDbTable()->getAdapter()->fetchPairs($select);

        return $data;
    }

    /**
     * @param string $templateName template name
     * @return mixed|null
     */
    public function findPageIdByTemplateName($templateName)
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto('template_id = ?', $templateName);
        $select = $this->getDbTable()->getAdapter()->select()->from('page')->where($where);
        return $this->getDbTable()->getAdapter()->fetchOne($select);
    }


}
