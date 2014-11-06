<?php

/**
 * Search
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_Search_Search extends Widgets_Abstract
{
    const INDEX_FOLDER        = 'search';

    const PAGE_OPTION_SEARCH  = 'option_search';

    const SEARCH_LIMIT_RESULT = 20;

    const OPTION_SORT_RECENT  = 'sort-recent';

    const INDEX_LOCK_CACHE_ID = 'buildingindex';

    const INDEX_CACHE_PREFIX  = 'search_index';

    private $_websiteHelper   = null;

    protected function _init()
    {
        parent::_init();
        $this->_view = new Zend_View(array(
            'scriptPath' => dirname(__FILE__) . '/views'
        ));
        $this->_view->setHelperPath(APPLICATION_PATH . '/views/helpers/');

        $this->_websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');

        if (in_array('results', $this->_options) || in_array('links', $this->_options)) {
            $this->_cacheable = false;
        }

        $this->_cacheId = strtolower(__CLASS__).'_lifeTime_'.$this->_cacheLifeTime;
        array_push($this->_cacheTags, strtolower(__CLASS__));
    }

    protected function _load()
    {
        if (!is_array($this->_options)
            || empty($this->_options)
            || !isset($this->_options[0])
            || !$this->_options[0]
            || preg_match('~^\s*$~', $this->_options[0])
        ) {
            throw new Exceptions_SeotoasterWidgetException($this->_translator->translate('Not enough parameters'));
        }

        $rendererName = '_renderSearch' . ucfirst($this->_options[0]);
        if (method_exists($this, $rendererName)) {
            array_shift($this->_options);
            return $this->$rendererName($this->_options);
        }

        return $this->_renderSearchComplex();
    }

    /**
     * Renders search form widget
     * @return string   Widget html code
     * @throws Exceptions_SeotoasterWidgetException If search results page not provided or doesn't exists
     */
    private function _renderSearchForm()
    {
        $searchResultPage = Application_Model_Mappers_PageMapper::getInstance()->fetchByOption(
            self::PAGE_OPTION_SEARCH,
            true
        );

        if (!$searchResultPage instanceof Application_Model_Models_Page) {
            if (isset($this->_options[0]) && intval($this->_options[0])) {
                $searchResultPage = Application_Model_Mappers_PageMapper::getInstance()->find(
                    intval($this->_options[0])
                );
                if (!$searchResultPage instanceof Application_Model_Models_Page) {
                    throw new Exceptions_SeotoasterWidgetException($this->_translator->translate(
                        'Search results page not found'
                    ));
                }
            } else {
                throw new Exceptions_SeotoasterWidgetException($this->_translator->translate(
                    'Search results page is not selected'
                ));
            }
        }

        $searchForm = new Application_Form_Search();
        $searchFormAction = $searchResultPage->getUrl();
        if ($searchFormAction !== 'index.html') {
            $searchForm->setAction($this->_websiteHelper->getUrl() . $searchFormAction);
        } else {
            $searchForm->setAction($this->_websiteHelper->getUrl());
        }
        $this->_view->searchForm = $searchForm;

        $this->_view->showReindexOption = Tools_Security_Acl::isAllowed(
                Tools_Security_Acl::RESOURCE_USERS
            ) && Tools_Search_Tools::isEmpty();

        return $this->_view->render('form.phtml');
    }

    private function _renderSearchResults()
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();

        $params = $request->getParams();

        $results = array();
        $limit = is_numeric(end($this->_options)) ? filter_var(
            end($this->_options),
            FILTER_SANITIZE_NUMBER_INT
        ) : self::SEARCH_LIMIT_RESULT;

        // check for image option
        if (in_array('img', $this->_options)) {
            $this->_view->useImage = 'img';
        } elseif (in_array('imgc', $this->_options)) {
            $this->_view->useImage = 'imgc';
        } else {
            $this->_view->useImage = false;
        }

        $this->_view->websiteUrl = $this->_websiteHelper->getUrl();

        if ($request->has('search')) {
            $searchTerm = strip_tags($request->getParam('search'));
            if (mb_strlen($searchTerm) < 3) {
                return sprintf(
                    $this->_translator->translate(
                        'Search error "%s". The request string should have more than 3 letters.'
                    ),
                    $searchTerm
                );
            }
            $this->_view->urlData = array('search' => $searchTerm);
            $results = $this->_searchResultsByTerm($searchTerm);
        } elseif ($request->has('queryID')) {
            $queryID = filter_var($request->getParam('queryID'), FILTER_SANITIZE_STRING);
            $this->_view->urlData = array('queryID' => $queryID);
            $results = $this->_searchResultsByQueryID($queryID);
        }

        if (is_array($results) && empty($results)) {
            return '{$content:nothingfound}';
        }

        $pager = Zend_Paginator::factory($results);
        $pager->setDefaultItemCountPerPage($limit);
        if (isset($params['showpage'])) {
            $pager->setCurrentPageNumber(filter_var($params['showpage'], FILTER_SANITIZE_NUMBER_INT));
        }
        $this->_view->pager = $pager;

        return $this->_view->render('results.phtml');
    }

    private function _searchResultsByTerm($searchTerm)
    {
        $searchForm = new Application_Form_Search();
        if ($searchForm->getElement('search')->isValid($searchTerm)) {
            $searchTerm             = $searchForm->getElement('search')->getValue();
            $this->_view->pagerData = array('search' => $searchTerm);
            $cacheId                = strtolower(__FUNCTION__);
            $key                    = md5($searchTerm.implode(',', $this->_options));
            $cachePrefix            = strtolower(__CLASS__);
            if ($this->_developerModeStatus) {
                $this->_cache = Zend_Controller_Action_HelperBroker::getStaticHelper('Cache');
            }
            if (null === ($searchResults = $this->_cache->load($cacheId, $cachePrefix))
                || !isset($searchResults['data'][$key])
            ) {
                $toasterSearchIndex = Tools_Search_Tools::initIndex();
                $toasterSearchIndex->setResultSetLimit(self::SEARCH_LIMIT_RESULT * 10);
                try {
                    if (in_array(self::OPTION_SORT_RECENT, $this->_options)
                        && array_key_exists('modified', $toasterSearchIndex->getFieldNames())) {
                        $hits = $toasterSearchIndex->find($searchTerm, 'modified', SORT_DESC);
                    } else {
                        $hits = $toasterSearchIndex->find($searchTerm);
                    }
                } catch (Exception $e) {
                    throw new Exceptions_SeotoasterWidgetException($e->getMessage());
                }
                $cacheTags     = array('search_' . $searchTerm);
                $searchResults = array_map(
                    function ($hit) use (&$cacheTags) {
                        array_push($cacheTags, 'pageid_' . $hit->pageId);
                        try {
                            // checking if page is in drafts
                            $draft = (bool)$hit->draft;
                        } catch (Zend_Search_Lucene_Exception $e) {
                            // seems we are on old release
                            $draft = false;
                        }
                        if (!$draft) {
                            return array(
                                'pageId'     => $hit->pageId,
                                'url'        => $hit->url,
                                'h1'         => $hit->h1,
                                'navName'    => $hit->navName,
                                'teaserText' => $hit->teaserText
                            );
                        }
                    },
                    $hits
                );

                $searchResults = array_filter($searchResults);
                array_merge($this->_cacheTags, $cacheTags);
                $this->_cache->update(
                    $cacheId,
                    $key,
                    $searchResults,
                    $cachePrefix,
                    $this->_cacheTags,
                    Helpers_Action_Cache::CACHE_SHORT
                );

                return $searchResults;
            }

            return $searchResults['data'][$key];
        }
        else {
            $msg = $searchForm->getElement('search')->getMessages();
            $error = $this->_translator->translate('Search error. ' . implode(PHP_EOL, $msg));
            throw new Exceptions_SeotoasterWidgetException($error);
        }
    }

    private function _searchResultsByQueryID($queryID)
    {
        $this->_cachePrefix .= 'qid_';
        if (null === ($results = $this->_cache->load($queryID, $this->_cachePrefix))) {
            $results = array();
            /**
             * @var $flashHelper Zend_Controller_Action_Helper_FlashMessenger
             */
            $flashHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger');
            $msgBuffer = $flashHelper->getMessages($queryID);

            if (!empty($msgBuffer)) {
                $nameValuePairs = $msgBuffer[0];
                unset($msgBuffer);

                $pageIDs = Application_Model_Mappers_ContainerMapper::getInstance()->findByContainerNameWithContent(
                    $nameValuePairs
                );
                if (!empty($pageIDs)) {
                    // TODO: compare performance
//                    $pageList = Application_Model_Mappers_PageMapper::getInstance()->find($containerData);
                    $pageMapper = Application_Model_Mappers_PageMapper::getInstance();
                    $where = $pageMapper->getDbTable()->getAdapter()->quoteInto('id IN (?)', array_values($pageIDs));
                    $pages = $pageMapper->fetchAll($where);
                    foreach ($pages as $page) {
                        array_push($this->_cacheTags, 'pageid_' . $page->getId());
                        if ($page->getDraft()) {
                            continue;
                        }
                        $results[] = array(
                            'pageId'     => $page->getId(),
                            'url'        => $page->getUrl(),
                            'h1'         => $page->getH1(),
                            'teaserText' => $page->getTeaserText(),
                            'navName'    => $page->getNavName()
                        );
                    }
                }
            }
            $this->_cache->save(
                $queryID,
                $results,
                $this->_cachePrefix,
                $this->_cacheTags,
                Helpers_Action_Cache::CACHE_WEEK
            );
        }

        return $results;
    }

//   // removed in current version
//    public static function getWidgetMakerContent() {
//		$translator = Zend_Registry::get('Zend_Translate');
//		$view = new Zend_View(array(
//			'scriptPath' => dirname(__FILE__) . '/views'
//		));
//		$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
//		$data = array(
//			'title'   => $translator->translate('Search engine'),
//			'content' => $view->render('wmcontent.phtml'),
//			'icons'   => array(
//				$websiteHelper->getUrl() . 'system/images/widgets/search.png',
//			)
//		);
//
//		unset($view);
//		return $data;
//	}

    private function _renderSearchComplex()
    {
        if (!empty($this->_options[0])) {
            if ($this->_options[0] === 'select' && empty($this->_options[1])) {
                $prepopSearchName = $this->_options[1];
            } else {
                $prepopSearchName = $this->_options[0];
            }
            $prepopWithNameList = Application_Model_Mappers_ContainerMapper::getInstance()->findByContainerName(
                $prepopSearchName,
                true
            );
            if ($prepopWithNameList) {
                $this->_view->prepopName = $prepopSearchName;
                $this->_view->prepopWithNameList = $prepopWithNameList;
                return $this->_view->render('searchForm.phtml');
            }
        }
    }

    private function _renderSearchButton($options)
    {
        $searhResultPage = Application_Model_Mappers_PageMapper::getInstance()->fetchByOption(self::PAGE_OPTION_SEARCH);
        if (!empty($searhResultPage)) {
            $seacrhResultPageId = $searhResultPage[0]->getId();
        }
        if (isset($options[0])) {
            $seacrhResultPageId = $options[0];
        }
        if (isset($seacrhResultPageId)) {
            $this->_view->pageResultsPage = $seacrhResultPageId;
            return $this->_view->render('searchButton.phtml');
        }
    }

    private function _renderSearchLinks($optionsArray)
    {
        if (isset($optionsArray[0])) {
            $containerMapper = Application_Model_Mappers_ContainerMapper::getInstance();
            $this->_view->addHelperPath('ZendX/JQuery/View/Helper/', 'ZendX_JQuery_View_Helper');
            if (strtolower($optionsArray[0]) != 'thispage') {
                $prepopAllLinks = $containerMapper->findByContainerName($optionsArray[0], true);
                if (!empty($prepopAllLinks)) {
                    foreach ($prepopAllLinks as $prepopData) {
                        $contentArray[] = $prepopData['content'];
                    }
                    asort($contentArray);
                    $this->_view->prepopName = $optionsArray[0];
                    $this->_view->prepopLinks = $contentArray;
                    return $this->_view->render('links.phtml');
                }
            } else {
                $prepopPageLinks = $containerMapper->findPreposByPageId($this->_toasterOptions['id']);
                if (!empty($prepopPageLinks)) {
                    $this->_view->prepopPageLinks = $prepopPageLinks;
                    return $this->_view->render('prepopPageLinks.phtml');
                }
            }
        }
    }

    private function _renderSearchAdvanced()
    {
        $this->_cachePrefix .= 'advanced_';
        if (is_array($this->_options) && !empty($this->_options)) {
            $prepopWithQuantity = array();
            $prepopLabels = array();
            $prepopNames = explode('|', $this->_options[0]);
            foreach ($prepopNames as $key => $prepopName) {
                if (mb_strpos($prepopName, '(#)') !== false) {
                    $prepopWithQuantity[] = str_replace('(#)', '', $prepopName);
                    $prepopNames[$key] = str_replace('(#)', '', $prepopName);
                }
            }
            if (isset($this->_options[1]) && mb_strpos($this->_options[1], '|') !== false) {
                $prepopLabels = explode('|', $this->_options[1]);
            }
            if (count($prepopNames) == count($prepopLabels)) {
                $prepopLabels = array_combine($prepopNames, $prepopLabels);
            }

            if (end($this->_options) === 'select') {
                $cacheKey = str_replace('(#)', 'N', $this->_options[0]);
                if (null === ($prepopSearchData = $this->_cache->load($cacheKey, $this->_cachePrefix))) {
                    $prepopWithNameList = Application_Model_Mappers_ContainerMapper::getInstance(
                    )->findByContainerNames($prepopNames);
                    if (!empty($prepopWithNameList)) {
                        foreach ($prepopWithNameList as $prepopWithName) {
                            //adding cache tags
                            array_push(
                                $this->_cacheTags,
                                $prepopWithName['name'] . '_' . $prepopWithName['container_type'] . '_pid_' . $prepopWithName['page_id']
                            );
                            $searchArray[$prepopWithName['page_id']][$prepopWithName['name']] = $prepopWithName['content'];
                            $prepopNamePageIds[$prepopWithName['name']][$prepopWithName['content']][$prepopWithName['page_id']] = $prepopWithName['page_id'];
                            $prepopNameValues[$prepopWithName['name']][$prepopWithName['content']]['content'] = $prepopWithName['content'];
                            if (isset($prepopNameValues[$prepopWithName['name']][$prepopWithName['content']]['content'])
                                && $prepopNameValues[$prepopWithName['name']][$prepopWithName['content']]['content'] == $prepopWithName['content']
                            ) {
                                if (!isset($prepopNameValues[$prepopWithName['name']][$prepopWithName['content']]['quantity'])) {
                                    $prepopNameValues[$prepopWithName['name']][$prepopWithName['content']]['quantity'] = 0;
                                }
                                $prepopNameValues[$prepopWithName['name']][$prepopWithName['content']]['quantity'] += 1;
                            }
                        }
                    }
                    $prepopSearchData = array(
                        'searchArray'       => $searchArray,
                        'prepopNamePageIds' => $prepopNamePageIds,
                        'prepopNameValues'  => $prepopNameValues
                    );
                    //saving to cache
                    $this->_cache->save(
                        $cacheKey,
                        $prepopSearchData,
                        $this->_cachePrefix,
                        array_unique($this->_cacheTags),
                        Helpers_Action_Cache::CACHE_NORMAL
                    );
                }
                $this->_view->addHelperPath('ZendX/JQuery/View/Helper/', 'ZendX_JQuery_View_Helper');
                $this->_view->prepopNames = $prepopNames;
                $this->_view->prepopLabels = $prepopLabels;
                $this->_view->websiteUrl = $this->_toasterOptions['websiteUrl'];
                $this->_view->searchArray = json_encode($prepopSearchData['searchArray']);
                $this->_view->prepopNamePageIds = json_encode($prepopSearchData['prepopNamePageIds']);
                $this->_view->prepopWithQuantity = $prepopWithQuantity;
                $this->_view->prepopNameValues = array_reverse($prepopSearchData['prepopNameValues']);
                return $this->_view->render('advancedPrepopSearch.phtml');
            }
        }
    }

    public static function getAllowedOptions()
    {
        $translator = Zend_Registry::get('Zend_Translate');
        return array(
            array(
                'alias'  => $translator->translate('Search with prepops as links'),
                'option' => 'search:links:change_to_the_your_prepop_name'
            ),
            array(
                'alias'  => $translator->translate('Search with prepops as select'),
                'option' => 'search:select:change_to_the_your_prepop_name'
            ),
            array(
                'alias'  => $translator->translate('Prepop search button'),
                'option' => 'search:button'
            ),
            array(
                'alias'  => $translator->translate('Search form'),
                'option' => 'search:form'
            ),
            array(
                'alias'  => $translator->translate('Search results'),
                'option' => 'search:results'
            )
        );
    }
}
