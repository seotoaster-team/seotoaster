<?php
/**
 * SearchController
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class SearchController extends Zend_Controller_Action {

    const PAGE_OPTION_SEARCH      = 'option_search';
    
	public function init() {
		parent::init();
		$this->view->websiteUrl = $this->_helper->website->getUrl();
	}

	public function searchAction() {
		$this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

		if($this->getRequest()->isPost()) {
			$resultsHits    = array();
			$searchTerm     = filter_var($this->getRequest()->getParam('search'), FILTER_SANITIZE_STRING);

            //preparing searchTerm to fit multiple characters wildcard
            $searchTerm = trim($searchTerm, '*') . '*';

            $resultsPageId = filter_var($this->getRequest()->getParam('resultsPageId'), FILTER_VALIDATE_INT);
            $pageToRedirect = Application_Model_Mappers_PageMapper::getInstance()->find($resultsPageId);

//            $searchIndexDirPath = $this->_helper->website->getPath() . 'cache/' . Widgets_Search_Search::INDEX_FOLDER;

            //attempt to create search index folder if not exists
//            if(!is_dir($searchIndexDirPath)) {
//                if(!Tools_Filesystem_Tools::mkDir($searchIndexDirPath)) {
//                    $this->_helper->session->searchHits = 'System is unable to create search index directory. Please create it manually. The path is: ' . $searchIndexDirPath;
//                    $this->redirect($this->_helper->website->getUrl() . $pageToRedirect->getUrl());
//                }
//            }

//            $searchIndexFiles   = Tools_Filesystem_Tools::scanDirectory($searchIndexDirPath);
//            if(empty($searchIndexFiles)) {
//                Tools_Search_Tools::renewIndex(true);
//            }

            $toasterSearchIndex = Tools_Search_Tools::initIndex();
            $toasterSearchIndex->setResultSetLimit(Widgets_Search_Search::SEARCH_LIMIT_RESULT*10);

            try {
                $searchHits = $toasterSearchIndex->find($searchTerm);
            } catch (Exception $e) {
                $this->_helper->session->searchHits = $this->_helper->language->translate(
                    'Nothing found. You need at least 3 characters to start search.'
                );
                $this->redirect($this->_helper->website->getUrl() . $pageToRedirect->getUrl());
            }
            if (is_array($searchHits) && !empty($searchHits)) {
                foreach ($searchHits as $hit) {
                    $resultsHits[] = array(
                        'pageId'     => $hit->pageId,
                        'url'        => $hit->url,
                        'h1'         => $hit->h1,
                        'pageTeaser' => $hit->pageTeaser,
                        'navName'    => $hit->navName,
                        'preview'    => base64_decode($hit->pageImage)
                    );
                }
                $this->_helper->session->searchHits = $resultsHits;
            } else {
                $this->_helper->session->searchHits = '{$content:nothingfound}';
            }

            $this->redirect($this->_helper->website->getUrl() . $pageToRedirect->getUrl());
		}
	}

    public function complexsearchAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $containersNames = $this->_request->getParam('containerNames');
        $searchValues = $this->_request->getParam('searchValues');
        $resultsPageId  = filter_var($this->getRequest()->getParam('resultsPageId'), FILTER_VALIDATE_INT);
        $pageMapper = Application_Model_Mappers_PageMapper::getInstance();
        $redirectPage = 'index.html';
        if(!$resultsPageId === false){
            $pageToRedirect = $pageMapper->find($resultsPageId);
            $redirectPage = $pageToRedirect->getUrl(); 
        } else {
            $pageToRedirect = $pageMapper->fetchByOption(self::PAGE_OPTION_SEARCH);
            if(!empty($pageToRedirect)){
                $redirectPage = $pageToRedirect[0]->getUrl();
            }
        }
        $containerContentArray = array_combine($containersNames, $searchValues);
        $queryID = md5(serialize($containerContentArray));
        $this->_helper->flashMessenger->addMessage($containerContentArray, $queryID);

        echo json_encode(
            array(
                'redirect' => $this->_helper->website->getUrl() . $redirectPage . '?' . http_build_query(
                            array('queryID' => $queryID)
                        )
            )
        );
    }



}
