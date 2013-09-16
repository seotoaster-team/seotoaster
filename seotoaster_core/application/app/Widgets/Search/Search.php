<?php

/**
 * Search
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_Search_Search extends Widgets_Abstract {

	const INDEX_FOLDER        = 'search';
    const PAGE_OPTION_SEARCH  = 'option_search';
    const SEARCH_LIMIT_RESULT = 20;

    const INDEX_LOCK_CACHE_ID  = 'buildingindex';
    const INDEX_CACHE_PREFIX   = 'search_index';

	private $_websiteHelper = null;

	protected function _init() {
		parent::_init();
		$this->_view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));
		$this->_websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');

		$this->_cacheable = false;
	}

	protected function _load() {
		if(!is_array($this->_options) || empty($this->_options) || !isset($this->_options[0]) || !$this->_options[0] || preg_match('~^\s*$~', $this->_options[0])) {
			throw new Exceptions_SeotoasterWidgetException($this->_translator->translate('Not enough parameters'));
		}
//        $optionsArray = $this->_options;
		$rendererName = '_renderSearch' . ucfirst(array_shift($this->_options));
		if(method_exists($this, $rendererName)) {
			return $this->$rendererName($this->_options);
		}
	}

    /**
     * Renders search form widget
     * @return string   Widget html code
     * @throws Exceptions_SeotoasterWidgetException If search results page not provided or doesn't exists
     */
    private function _renderSearchForm() {
        $searchResultPage = Application_Model_Mappers_PageMapper::getInstance()->fetchByOption(self::PAGE_OPTION_SEARCH, true);

        if (!$searchResultPage instanceof Application_Model_Models_Page) {
            if (isset($this->_options[0]) && intval($this->_options[0])) {
                $searchResultPage = Application_Model_Mappers_PageMapper::getInstance()->find(intval($this->_options[0]));
                if (!$searchResultPage instanceof Application_Model_Models_Page) {
                    throw new Exceptions_SeotoasterWidgetException($this->_translator->translate('Search results page not found'));
                }
            } else {
                throw new Exceptions_SeotoasterWidgetException($this->_translator->translate('Search results page is not selected'));
            }
        }

		$searchForm = new Application_Form_Search();
		$searchForm->setAction($this->_websiteHelper->getUrl() . $searchResultPage->getUrl());
		$this->_view->searchForm = $searchForm;

        if (Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_USERS)) {
            if (Tools_Search_Tools::isEmpty() && null === ($indexLock = $this->_cache->load(self::INDEX_LOCK_CACHE_ID,self::INDEX_CACHE_PREFIX))) {
                $indexLock = array(
                    'limit' => self::SEARCH_LIMIT_RESULT,
                    'offset' => 0
                );
                $this->_cache->save(self::INDEX_LOCK_CACHE_ID, $indexLock, self::INDEX_CACHE_PREFIX);
            }
            $this->_view->runIndexing = true;
        }

		return $this->_view->render('form.phtml');
	}

	private function _renderSearchResults() {
//		$sessionHelper                = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
//		$totalHits                    = $sessionHelper->searchHits;
        $limit                        = isset($this->_options[1]) ? $this->_options[1] : self::SEARCH_LIMIT_RESULT;

        $searchForm = new Application_Form_Search();

        $request = Zend_Controller_Front::getInstance()->getRequest();

        if (!$request->has('search')){
            return '';
        }

        $searchTerm = filter_var($request->getParam('search'), FILTER_SANITIZE_STRING);

        if (!empty($searchTerm) && $searchForm->getElement('search')->isValid($searchTerm)){
            $searchTerm = trim($searchTerm, '*') . '*';
            if (null === ($searchResults = $this->_cache->load($searchTerm, strtolower(__CLASS__)))){
                $toasterSearchIndex = Tools_Search_Tools::initIndex();
                $toasterSearchIndex->setResultSetLimit(self::SEARCH_LIMIT_RESULT*10);
                $hits = $toasterSearchIndex->find($searchTerm);
                $searchResults = array_map(function($hit){
                        return array(
                            'pageId' => $hit->pageId,
                            'url' => $hit->url,
                            'h1'  => $hit->h1,
                            'navName' => $hit->navName,
                            'pageTeaser' => $hit->pageTeaser,
                            'pagePreview' => $hit->pagePreview
                        );
                    }, $hits);

                $this->_cache->save($searchTerm, $searchResults, strtolower(__CLASS__), array('search'), Helpers_Action_Cache::CACHE_LONG);
            }
            if (empty($searchResults)) {
                $this->_view->hits = '{$content:nothingfound}';
            } else {
                $totalHits = count($searchResults);
                if ($totalHits > $limit){
                    $this->_view->totalHits = $totalHits;
                    $this->_view->limit = $limit;
                    $searchResults = array_slice($searchResults, 0, $limit);
                }
                $this->_view->hits = $searchResults;
            }
        } else {
            // TODO $searchTerm validation error
            $this->_view->hits = $this->_translator->translate(
                'Nothing found. You need at least 3 characters to start search.'
            );
        }


        $this->_view->useImage = (isset($this->_options[0]) && ($this->_options[0] == 'img' || $this->_options[0] == 'imgc')) ? $this->_options[0] : false;

		return $this->_view->render('results.phtml');
	}

	public static function getWidgetMakerContent() {
		$translator = Zend_Registry::get('Zend_Translate');
		$view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));
		$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$data = array(
			'title'   => $translator->translate('Search engine'),
			'content' => $view->render('wmcontent.phtml'),
			'icons'   => array(
				$websiteHelper->getUrl() . 'system/images/widgets/search.png',
			)
		);

		unset($view);
		return $data;
	}

    private function _renderSearchComplex($optionsArray){
        if(isset($optionsArray[0])){
            if($optionsArray[0] == 'select' && isset($optionsArray[1])){
                $prepopSearchName =  $optionsArray[1];
            }else{
                $prepopSearchName = $optionsArray[0];
            }
            $prepopWithNameList = Application_Model_Mappers_ContainerMapper::getInstance()->findByContainerName($prepopSearchName);
            if($prepopWithNameList){
                $this->_view->prepopWithName = $prepopWithNameList;
                foreach($prepopWithNameList as $prepopData){
                    $contentArray[] = $prepopData->getContent();
                }
                asort($contentArray);
                $this->_view->prepopWithNameList = array_unique($contentArray);
                return $this->_view->render('searchForm.phtml');
            }

        }
    }

    private function _renderSearchButton($options) {
        $searhResultPage = Application_Model_Mappers_PageMapper::getInstance()->fetchByOption(self::PAGE_OPTION_SEARCH);
        if(!empty($searhResultPage)){
            $seacrhResultPageId = $searhResultPage[0]->getId();
        }
        if(isset($options[0])){
            $seacrhResultPageId = $options[0];
        }
        if(isset($seacrhResultPageId)){
            $this->_view->pageResultsPage = $seacrhResultPageId;
            return $this->_view->render('searchButton.phtml');
        }
    }

    private function _renderSearchLinks($optionsArray){
        if(isset($optionsArray[0]) && isset($optionsArray[1])){
            $containerMapper = Application_Model_Mappers_ContainerMapper::getInstance();
            $this->_view->addHelperPath('ZendX/JQuery/View/Helper/', 'ZendX_JQuery_View_Helper');
            if(strtolower($optionsArray[1]) != 'thispage'){
                $prepopAllLinks = $containerMapper->findByContainerName($optionsArray[1], true);
                if(!empty($prepopAllLinks)){
                    foreach($prepopAllLinks as $prepopData){
                        $contentArray[] = $prepopData['content'];
                    }
                    asort($contentArray);
                    $this->_view->prepopName = $optionsArray[1];
                    $this->_view->prepopLinks = $contentArray;
                    return $this->_view->render('links.phtml');
                }
            }else{
                $prepopPageLinks = $containerMapper->findPreposByPageId($this->_toasterOptions['id']);
                if(!empty($prepopPageLinks)){
                    $this->_view->prepopPageLinks = $prepopPageLinks;
                    return $this->_view->render('prepopPageLinks.phtml');
                }
            }
        }
    }

    private function _renderSearchAdvanced($optionsArray){
        if(isset($optionsArray[1]) && preg_match('~\|~', $optionsArray[1]) && isset($optionsArray[2])){
            $cacheHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('cache');
            $prepopWithQuantity = array();
            $prepopLabels = array();
            $prepopNames = explode('|', $optionsArray[1]);
            foreach($prepopNames as $key => $prepopName){
                if(preg_match('(#)', $prepopName)){
                    $prepopWithQuantity[] = str_replace('(#)','',$prepopName);
                    $prepopNames[$key] = str_replace('(#)','',$prepopName);
                }
            }
            if(isset($optionsArray[2]) && preg_match('~\|~', $optionsArray[2])){
                $prepopLabels =  explode('|', $optionsArray[2]);
            }
            if(count($prepopNames) == count($prepopLabels)){
                $prepopLabels = array_combine($prepopNames, $prepopLabels);
            }

            if(end($optionsArray) == 'select'){
                $cacheKey = str_replace('(#)','_',$optionsArray[1]);
                if (null === ($prepopSearchData = $cacheHelper->load('search_prepop_'.$cacheKey, 'search_prepop'))){
                    $prepopWithNameList = Application_Model_Mappers_ContainerMapper::getInstance()->findByContainerNames($prepopNames);
                    if(!empty($prepopWithNameList)){
                        foreach($prepopWithNameList as $prepopWithName){
                            $searchArray[$prepopWithName->getPageId()][$prepopWithName->getName()] = $prepopWithName->getContent();
                            $prepopNamePageIds[$prepopWithName->getName()][$prepopWithName->getContent()][$prepopWithName->getPageId()] = $prepopWithName->getPageId();
                            $prepopNameValues[$prepopWithName->getName()][$prepopWithName->getContent()]['content'] = $prepopWithName->getContent();
                            if(isset($prepopNameValues[$prepopWithName->getName()][$prepopWithName->getContent()]['content']) && $prepopNameValues[$prepopWithName->getName()][$prepopWithName->getContent()]['content'] == $prepopWithName->getContent()){
                                $prepopNameValues[$prepopWithName->getName()][$prepopWithName->getContent()]['quantity'] = $prepopNameValues[$prepopWithName->getName()][$prepopWithName->getContent()]['quantity'] + 1;
                            }
                        }
                    }
                    $prepopSearchData = array('searchArray'=>$searchArray, 'prepopNamePageIds'=>$prepopNamePageIds, 'prepopNameValues'=>$prepopNameValues);
                    $cacheHelper->save('search_prepop_'.$cacheKey, $prepopSearchData, 'search_prepop', array(), Helpers_Action_Cache::CACHE_SHORT);
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

    public static function getAllowedOptions() {
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
				'alias'  => $translator->translate('Prepop seacrh button'),
				'option' => 'search:button'
			),
            array(
				'alias'  => $translator->translate('Search form'),
				'option' => 'search:form'
			)
        );
    }
}
