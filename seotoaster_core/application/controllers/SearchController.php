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
		$this->_helper->AjaxContext()->addActionContexts(array(
			'managesearchindex' => 'json'
		))->initContext('json');

	}

	public function searchAction() {
		$this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

		if($this->getRequest()->isPost()) {
			$resultsHits    = array();
			$searchTerm     = filter_var($this->getRequest()->getParam('search'), FILTER_SANITIZE_STRING);

            //preparing searchTerm to fit multiple charachters wildcard
            $searchTerm = trim($searchTerm, '*') . '*';

			$resultsPageId  = filter_var($this->getRequest()->getParam('resultsPageId'), FILTER_VALIDATE_INT);
			$pageToRedirect = Application_Model_Mappers_PageMapper::getInstance()->find($resultsPageId);

            $searchIndexDirPath = $this->_helper->website->getPath() . 'cache/' . Widgets_Search_Search::INDEX_FOLDER;

            //attempt to create search index folder if not exists
            if(!is_dir($searchIndexDirPath)) {
                if(!Tools_Filesystem_Tools::mkDir($searchIndexDirPath)) {
                    $this->_helper->session->searchHits = 'System is unable to create search index directory. Please create it manually. The path is: ' . $searchIndexDirPath;
                    $this->_redirect($this->_helper->website->getUrl() . $pageToRedirect->getUrl());
                }
            }

            $searchIndexFiles   = Tools_Filesystem_Tools::scanDirectory($searchIndexDirPath);
            if(empty($searchIndexFiles)) {
                Tools_Search_Tools::renewIndex(true);
            }

			$toasterSearchIndex = Zend_Search_Lucene::open($searchIndexDirPath);
            $toasterSearchIndex->setResultSetLimit(256);

            try{
                $searchHits         = $toasterSearchIndex->find($searchTerm);
            }catch(Exception $e){
                $this->_helper->session->searchHits = $this->_helper->language->translate('Nothing found. You need at least 3 characters to start search.');
                $this->_redirect($this->_helper->website->getUrl() . $pageToRedirect->getUrl());
            }
			if(is_array($searchHits) && !empty($searchHits)) {
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
			}
			else {
				$this->_helper->session->searchHits = '{$content:nothingfound}';
			}

			$this->_redirect($this->_helper->website->getUrl() . $pageToRedirect->getUrl());
		}
	}

	public function managesearchindexAction() {
		if($this->getRequest()->isPost()) {
			$doAction = $this->getRequest()->getParam('doaction');
			switch ($doAction) {
				case 'renew':
					Tools_Search_Tools::renewIndex();
					$this->_helper->response->success($this->_helper->language->translate('Search index is up to date now.'));
				break;
				case 'remove':
					Tools_Search_Tools::removeIndex();
				break;
			}
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
        }else{
            $pageToRedirect = $pageMapper->fetchByOption(self::PAGE_OPTION_SEARCH);
            if(!empty($pageToRedirect)){
                $redirectPage = $pageToRedirect[0]->getUrl();
            }
        }
        $containerContentArray = array_combine($containersNames, $searchValues);
        $containerData = Application_Model_Mappers_ContainerMapper::getInstance()->findByContainerNameWithContent($containerContentArray);
        $findUrlList = array();
        if(!empty($containerData)){
            $pageList = $pageMapper->find($containerData);
            foreach($pageList as $page){
                    $previewImage = '';  
                    if((bool)$page->getPreviewImage()){
                      $previewImage = Tools_Page_Tools::getPreview($page);
                    }
                    $resultsHits[] = array(
						'pageId'     => $page->getId(),
						'url'        => $page->getUrl(),
						'h1'         => $page->getH1(),
						'pageTeaser' => $page->getTeaserText(),
						'navName'    => $page->getNavName(),
                        'preview'    => $previewImage
					);
            }
            $this->_helper->session->searchHits = $resultsHits;
        }else {
            $this->_helper->session->searchHits = '{$content:nothingfound}';
        }
        echo json_encode(array('redirect'=>$this->_helper->website->getUrl() . $redirectPage));
    }

    public function showmoreresultsAction(){
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $searchLimit      = $this->_request->getParam('searchLimit');
        $searchUseImage   = $this->_request->getParam('searchUseImage');
        if(isset($this->_helper->session->totalHitsData)){
            $totalHits = $this->_helper->session->totalHitsData;
            $moreResults = 0;
            if(count($totalHits) >= $searchLimit){
                $hitsData = array_splice($totalHits, $searchLimit);
                $this->_helper->session->totalHitsData = $hitsData;
                $moreResults = 1;
            }else{
                unset($this->_helper->session->totalHitsData);
            }
            $this->view->useImage = $searchUseImage;
            $this->view->hits = $totalHits;
            $view = $this->view->render('backend/search/results.phtml');
            $this->_helper->response->success(array('searchResultsData'=>$view, 'moreResults'=>$moreResults));
        }else{
            $this->_helper->response->fail();
        }
    }
}
