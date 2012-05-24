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
			$resultsPageId  = filter_var($this->getRequest()->getParam('resultsPageId'), FILTER_VALIDATE_INT);
			$pageToRedirect = Application_Model_Mappers_PageMapper::getInstance()->find($resultsPageId);

            $searchIndexDirPath = $this->_helper->website->getPath() . 'cache/' . Widgets_Search_Search::INDEX_FOLDER;
            $searchIndexFiles   = Tools_Filesystem_Tools::scanDirectory($searchIndexDirPath);
            if(empty($searchIndexFiles)) {
                Tools_Search_Tools::renewIndex(true);
            }
			$toasterSearchIndex = Zend_Search_Lucene::open($this->_helper->website->getPath() . 'cache/' . Widgets_Search_Search::INDEX_FOLDER);
			$searchHits         = $toasterSearchIndex->find($searchTerm);
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
}

