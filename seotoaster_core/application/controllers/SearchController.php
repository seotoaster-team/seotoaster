<?php

/**
 * SearchController
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class SearchController extends Zend_Controller_Action {

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
				$this->_helper->session->searchHits = $this->_helper->language->translate('Nothing found');
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
        $pageToRedirect = $pageMapper->find($resultsPageId);
        $containerContentArray = array_combine($containersNames, $searchValues);
        $containerData = Application_Model_Mappers_ContainerMapper::getInstance()->findByConteinerNameWithContent($containerContentArray, array('6'));
        $findUrlList = array();
        if(!empty($containerData)){
            $pageList = $pageMapper->find($containerData);
            foreach($pageList as $page){
                  $resultsHits[] = array(
						'pageId'     => $page->getId(),
						'url'        => $page->getUrl(),
						'h1'         => $page->getH1(),
						'pageTeaser' => $page->getTeaserText(),
						'navName'    => $page->getNavName()
					);
            }
            $this->_helper->session->searchHits = $resultsHits;
        }else {
            $this->_helper->session->searchHits = $this->_helper->language->translate('Nothing found');
        }
        echo json_encode(array('redirect'=>$this->_helper->website->getUrl() . $pageToRedirect->getUrl()));
    }
    }
