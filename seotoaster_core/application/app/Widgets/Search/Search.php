<?php

/**
 * Search
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_Search_Search extends Widgets_Abstract {

	const INDEX_FOLDER       = 'search';
    const PAGE_OPTION_SEARCH = 'option_search';

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
        $optionsArray = $this->_options;
		$rendererName = '_renderSearch' . ucfirst(array_shift($this->_options));
		if(method_exists($this, $rendererName)) {
			return $this->$rendererName($this->_options);
		}
        if($rendererName == '_renderSearchButton'){
            return $this->_renderSearchButton($optionsArray);
        }
        if($rendererName == '_renderSearchLinks'){
            return $this->_renderLinks($optionsArray);
        }
        return $this->_renderComplexSearch($optionsArray);
	}

	private function _renderSearchForm() {
		if(isset($this->_options[0]) && intval($this->_options[0])) {
            $seacrhResultPageId = $this->_options[0];
        }
        $searhResultPage = Application_Model_Mappers_PageMapper::getInstance()->fetchByOption(self::PAGE_OPTION_SEARCH);
        if(!empty($searhResultPage)){
            $seacrhResultPageId = $searhResultPage[0]->getId();
        }
        if(!isset($seacrhResultPageId)){
            throw new Exceptions_SeotoasterWidgetException($this->_translator->translate('Search results page is not selected'));
        }
		$searchForm = new Application_Form_Search();
		$searchForm->setResultsPageId($seacrhResultPageId)
			->setAction($this->_websiteHelper->getUrl() . 'backend/search/search/');

		$this->_view->searchForm = $searchForm;
		$this->_view->renewIndex = $this->_isIndexRenewNeeded();
		return $this->_view->render('form.phtml');
	}

	private function _renderSearchResults() {
		$sessionHelper             = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
		$this->_view->useImage     = (isset($this->_options[0]) && ($this->_options[0] == 'img' || $this->_options[0] == 'imgc')) ? $this->_options[0] : false;
        $this->_view->hits         = $sessionHelper->searchHits;
        $sessionHelper->searchHits = null;
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

	private function _isIndexRenewNeeded() {
		//if role of the current user < member - we do not re-build index
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_PAGE_PROTECTED)) {
			return false;
		}
		if(($renewed = $this->_cache->load('indexRenewed', 'widget_search_index')) === null) {
			$this->_cache->save('indexRenewed', true, 'widget_search_index', array('search_index_renew'), (Helpers_Action_Cache::CACHE_LONG * 30));
			return true;
		}
		return false;
	}
    
    private function _renderComplexSearch($optionsArray){
        if(isset($optionsArray[0])){
            if($optionsArray[0] == 'select' && isset($optionsArray[1])){
                $prepopSearchName =  $optionsArray[1];
            }else{
                $prepopSearchName = $optionsArray[0];
            }
            $prepopWithNameList = Application_Model_Mappers_ContainerMapper::getInstance()->findByConteinerName($prepopSearchName);
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
    
    private function _renderSearchButton($optionsArray) {
        $searhResultPage = Application_Model_Mappers_PageMapper::getInstance()->fetchByOption(self::PAGE_OPTION_SEARCH);
        if(!empty($searhResultPage)){
            $seacrhResultPageId = $searhResultPage[0]->getId();
        }
        if(isset($optionsArray[0])){
            $seacrhResultPageId = $optionsArray[0];
        }
        if(isset($seacrhResultPageId)){
            $this->_view->pageResultsPage = $seacrhResultPageId;
            return $this->_view->render('searchButton.phtml');
        }
    }   
    
    private function _renderLinks($optionsArray){
        if(isset($optionsArray[0]) && isset($optionsArray[1])){
            $containerMapper = Application_Model_Mappers_ContainerMapper::getInstance();
            $this->_view->addHelperPath('ZendX/JQuery/View/Helper/', 'ZendX_JQuery_View_Helper');
            if(strtolower($optionsArray[1]) != 'thispage'){
                $prepopAllLinks = $containerMapper->findByConteinerName($optionsArray[1]);
                if(!empty($prepopAllLinks)){
                    foreach($prepopAllLinks as $prepopData){
                        $contentArray[] = $prepopData->getContent();
                    }
                    asort($contentArray);
                    $this->_view->prepopName = $optionsArray[1];
                    $this->_view->prepopLinks = array_unique($contentArray);
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
