<?php

class Application_Model_Mappers_PageMapper extends Application_Model_Mappers_Abstract {

	protected $_dbTable       = 'Application_Model_DbTable_Page';

	protected $_model         = 'Application_Model_Models_Page';

	protected $_optimized     = false;

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
    public function save($page) {
		if(!$page instanceof Application_Model_Models_Page) {
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
			'preview_image'       => $page->getPreviewImage()
		);


        if($page->getId()) {
            $this->getDbTable()->update($data, array('id = ?' => $page->getId()));
        } else {
            $pageId = $this->getDbTable()->insert($data);
            $page->setId($pageId);
        }

        //save page options
        $options = $page->getExtraOptions();
        $pageHasOptionTable = new Application_Model_DbTable_PageHasOption();
        if(!empty($options)) {
            $pageHasOptionTable->getAdapter()->beginTransaction();
            $pageHasOptionTable->delete($pageHasOptionTable->getAdapter()->quoteInto('page_id = ?', $page->getId()));
            foreach ($options as $option) {
                $pageHasOptionTable->insert(array(
                    'page_id'    => $page->getId(),
                    'option_id'  => $option
                ));
            }
            $pageHasOptionTable->getAdapter()->commit();
        } else {
            $pageHasOptionTable->delete($pageHasOptionTable->getAdapter()->quoteInto('page_id = ?', $page->getId()));
        }

        return $page;
	}

    public function fetchAll($where = '', $order = array(), $fetchSysPages = false, $originalsOnly = false) {
        $dbTable = $this->getDbTable();

		//exclude system pages from select
		$sysWhere  = $dbTable->getAdapter()->quoteInto("system = '?'", intval($fetchSysPages));
		$where    .= (($where) ? ' AND ' . $sysWhere : $sysWhere);
		$order[]   = 'order';
		$entries   = array();
		$resultSet = $dbTable->fetchAllPages($where, $order, $originalsOnly);

        if(null === $resultSet) {
			return null;
		}

	    $this->_originalsOnly = $originalsOnly;
        if(!$resultSet || empty($resultSet)) {
            return null;
        }

        /*foreach($resultSet as $row) {
            $row       = array_merge(array('extraOptions' => $this->getDbTable()->fetchPageOptions($row->id)), $row->toArray());
            $entries[] = $this->_toModel($row, $originalsOnly);
        }*/

        $model   = $this->_model;
        $entries = array_map(function($row) use(&$dbTable, $model, &$originalsOnly) {
            $row = array_merge(array('extraOptions' => $dbTable->fetchPageOptions($row['id'])), $row);
            return new $model($row);
        }, $resultSet->toArray());

		return $entries;
	}

    /**
     * Fetch pages by given option
     *
     * @param string $option
     * @param bool $firstOccurrenceOnly If true returns only first element of the result array
     * @return array|null
     */
    public function fetchByOption($option, $firstOccurrenceOnly = false) {
        $entries      = array();
        $optionTable  = new Application_Model_DbTable_PageOption();
        $optionRowset = $optionTable->find($option);
        if(!$optionRowset) {
            return null;
        }
        $optionRow = $optionRowset->current();
        if(!$optionRow) {
            return null;
        }
        $pagesRowset = $optionRow->findManyToManyRowset('Application_Model_DbTable_Page', 'Application_Model_DbTable_PageHasOption');
        foreach($pagesRowset as $pageRow) {
            $templateRow       = $pageRow->findParentRow('Application_Model_DbTable_Template');
            $pageRow           = $pageRow->toArray();
            $pageRow['content']= ($templateRow !== null) ? $templateRow->content : '';
            $entries[]         = $this->_toModel($pageRow);
        }
        if($firstOccurrenceOnly) {
            return (isset($entries[0])) ? $entries[0] : null;
        }
        return $entries;
    }

	public function fetchAllUrls() {
		$select = $this->getDbTable()->select(Zend_Db_Table::SELECT_WITHOUT_FROM_PART)
				->from($this->getDbTable()->info('name'), array('url'));
		return $this->getDbTable()->getAdapter()->fetchCol($select);
	}

	public function fetchAllStaticMenuPages() {
		$where = $this->getDbTable()->getAdapter()->quoteInto("show_in_menu = '?'", Application_Model_Models_Page::IN_STATICMENU);
		return $this->fetchAll($where);
	}

	public function fetchAllMainMenuPages() {
        return $this->getDbTable()->fetchAllMenu(Application_Model_Models_Page::IN_MAINMENU);
	}

	public function fetchAllDraftPages() {
		return $this->fetchAll('draft = 1', array(), true);
	}

	public function fetchAllNomenuPages() {
		$where = sprintf("show_in_menu = '%s' AND parent_id = %d", Application_Model_Models_Page::IN_NOMENU, Application_Model_Models_Page::IDCATEGORY_DEFAULT);
		return $this->fetchAll($where);
	}

	public function findByUrl($pageUrl) {
        if(!$pageUrl) {
            $pageUrl = Zend_Controller_Action_HelperBroker::getStaticHelper('website')->getDefaultPage();
        }
		$where = $this->getDbTable()->getAdapter()->quoteInto('url = ?', $pageUrl);
		$page  = $this->_findWhere($where);
		return ($page !== null) ? $page : $this->_findWhere($where, true);
	}

	public function findErrorLoginLanding() {
        return $this->fetchByOption(Application_Model_Models_Page::OPT_ERRLAND, true);
	}

	public function findMemberLanding() {
        return $this->fetchByOption(Application_Model_Models_Page::OPT_MEMLAND, true);
	}

	public function findSignupLandign() {
        return $this->fetchByOption(Application_Model_Models_Page::OPT_SIGNUPLAND, true);
	}

	public function findByNavName($navName) {
		$where = $this->getDbTable()->getAdapter()->quoteInto('nav_name = ?', $navName);
		return $this->_findWhere($where);
	}

	public function findByParentId($parentId) {
		$where = $this->getDbTable()->getAdapter()->quoteInto('parent_id = ?', $parentId);
		return $this->fetchAll($where);
	}

	public function selectCategoriesIdName($useNavName = false) {
		$result     = array();
		$categories = $this->findByParentId(0);
        if(empty($categories)) {
            return array();
        }
		foreach ($categories as $key => $category) {
			if($useNavName){
                $categoryName = ($category->getProtected()) ? ($category->getNavName() . '*') : $category->getNavName();
            }else{
                $categoryName = ($category->getProtected()) ? ($category->getH1() . '*') : $category->getH1();
            }
			$result[$category->getId()] = $categoryName;
		}
		return $result;
	}

	public function find404Page() {
        return $this->fetchByOption(Application_Model_Models_Page::OPT_404PAGE, true);
	}

	public function delete(Application_Model_Models_Page $page) {
		$where = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $page->getId());
		$deleteResult = $this->getDbTable()->delete($where);
		$page->notifyObservers();
		return $deleteResult;
	}

	public function fetchIdUrlPairs() {
		$select = $this->getDbTable()->select(Zend_Db_Table::SELECT_WITHOUT_FROM_PART)
				->from($this->getDbTable()->info('name'), array('id', 'url'))
				->order('url');

		return $this->getDbTable()->getAdapter()->fetchPairs($select);
	}

	protected function  _findWhere($where, $fetchSysPages = false) {
        $whereExploded = explode('=', $where);
        $spot          = strpos($whereExploded[0], '.');
        if($spot === false) {
            $whereExploded[0] = str_replace(substr($whereExploded[0], 0, $spot), '', $whereExploded[0]);
        }
        $where = implode('=', $whereExploded);
        $where = '(page.' . $where . ' OR optimized.' . $where . ')';

		$sysWhere = $this->getDbTable()->getAdapter()->quoteInto("system = '?'", intval($fetchSysPages));
		$where   .= (($where) ? ' AND ' . $sysWhere : $sysWhere);

        $row = $this->getDbTable()->fetchAllPages($where);
        if($row instanceof Zend_Db_Table_Rowset) {
            $row = $row->current();
        }

		$rowTemplate    = $row->findParentRow('Application_Model_DbTable_Template');
		$row            = $row->toArray();
		$row['content'] = ($rowTemplate !== null) ? $rowTemplate->content : '';

        //set an extra options for the page
        $row['extraOptions'] = $this->getDbTable()->fetchPageOptions($row['id']);

		unset($rowTemplate);
		return $this->_toModel($row);
	}

	/*protected function _optimizedRowWalk($row, $optimizedRowset = null) {
		if(!$optimizedRowset) {
			$optimizedRowset = $row->findDependentRowset('Application_Model_DbTable_Optimized')->current();
		}
		if($optimizedRowset === null) {
			return $row;
		}
		$this->_optimized = true;
		foreach($optimizedRowset as $propertyName => $propertyValue) {
			if($propertyValue && isset($row->$propertyName)) {
				$row->$propertyName = $propertyValue;
			}
		}
        return $row;
	}*/

	public function find($id, $originalsOnly = false) {
        if(!is_array($id)) {
            return $this->_findPage($id, $originalsOnly);
        }
        $pages = array();
        foreach($id as $pageId) {
            if(null !== ($page = $this->_findPage($pageId, $originalsOnly))) {
                $pages[] = $page;
            }
        }
        return $pages;
	}

    public function fetchAllByContent($content, $originalsOnly = false) {
        $pages = $this->getDbTable()->fetchAllByContent($content, $originalsOnly);
        if(!$pages || empty($pages)) {
            return null;
        }
        return array_map(function($pageData) {
            return new Application_Model_Models_Page($pageData);
        }, $pages);
    }

    protected function _findPage($id, $originalsOnly) {
        $row = $this->getDbTable()->findPage(intval($id));
        if(null == $row) {
            return null;
        }
        return $this->_toModel($row, $originalsOnly);
    }

	protected function _toModel($row, $originalsOnly = false) {
		if($row instanceof Zend_Db_Table_Row) {
			$row = $row->toArray();
		}
		if(!$originalsOnly && $this->_isOptimized($row)) {
            $this->_optimized           = false;
			$row['optimized']           = true;
            $row['h1']                  = isset($row['optimizedH1']) ? $row['optimizedH1'] : $row['h1'];
            $row['url']                 = isset($row['optimizedUrl']) ? $row['optimizedUrl'] : $row['url'];
            $row['header_title']        = isset($row['optimizedHeaderTitle']) ? $row['optimizedHeaderTitle'] : $row['header_title'];
            $row['nav_name']            = isset($row['optimizedNavName']) ? $row['optimizedNavName'] : $row['nav_name'];
            $row['targeted_key_phrase'] = isset($row['optimizedTargetedKeyPhrase']) ? $row['optimizedTargetedKeyPhrase'] : $row['targeted_key_phrase'];
            $row['meta_description']    = isset($row['optimizedMetaDescription']) ? $row['optimizedMetaDescription'] : $row['meta_description'];
            $row['meta_keywords']       = isset($row['optimizedMetaKeywords']) ? $row['optimizedMetaKeywords'] : $row['meta_keywords'];
			$row['teaser_text']         = isset($row['optimizedTeaserText']) ? $row['optimizedTeaserText'] : $row['teaser_text'];
		}
		return new $this->_model($row);
	}

    private function _isOptimized($row) {
        if($row instanceof Zend_Db_Table_Row) {
            $row = $row->toArray();
        }
        $isOptimized = false;
        foreach($row as $key => $value) {
            if(false !== (strpos($key, 'optimized', 0))) {
                $isOptimized = $isOptimized || (boolean)$value;
            }
        }
        return $isOptimized;
    }
}

