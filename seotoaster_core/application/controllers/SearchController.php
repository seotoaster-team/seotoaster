<?php

/**
 * SearchController
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class SearchController extends Zend_Controller_Action
{

    const PAGE_OPTION_SEARCH = 'option_search';

    public function init()
    {
        parent::init();
        $this->view->websiteUrl = $this->_helper->website->getUrl();
    }

    public function searchAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if ($this->getRequest()->isPost()) {
            $resultsHits = array();
            $searchTerm = filter_var($this->getRequest()->getParam('search'), FILTER_SANITIZE_STRING);

            //preparing searchTerm to fit multiple characters wildcard
            $searchTerm = trim($searchTerm, '*') . '*';

            $resultsPageId = filter_var($this->getRequest()->getParam('resultsPageId'), FILTER_VALIDATE_INT);
            $pageToRedirect = Application_Model_Mappers_PageMapper::getInstance()->find($resultsPageId);

            $toasterSearchIndex = Tools_Search_Tools::initIndex();
            $toasterSearchIndex->setResultSetLimit(Widgets_Search_Search::SEARCH_LIMIT_RESULT * 10);

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

    /**
     * Generates unique search ID for given search params and redirects to the page with search results
     */
    public function complexsearchAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $containersNames = $this->_request->getParam('containerNames', array());
        $searchValues = $this->_request->getParam('searchValues', array());
        $resultsPageId = filter_var($this->getRequest()->getParam('resultsPageId'), FILTER_VALIDATE_INT);
        $pageMapper = Application_Model_Mappers_PageMapper::getInstance();
        $redirectPage = 'index.html';
        if (!$resultsPageId === false) {
            $pageToRedirect = $pageMapper->find($resultsPageId);
            $redirectPage = $pageToRedirect->getUrl();
        } else {
            $pageToRedirect = $pageMapper->fetchByOption(self::PAGE_OPTION_SEARCH);
            if (!empty($pageToRedirect)) {
                $redirectPage = $pageToRedirect[0]->getUrl();
            }
        }

        $response = array(
            'redirect' => $this->_helper->website->getUrl() . $redirectPage
        );

        if (!empty($containersNames) && !empty($searchValues)) {
            $containerContentArray = array_combine($containersNames, $searchValues);
            $queryID = md5(serialize($containerContentArray));
            $this->_helper->flashMessenger->addMessage($containerContentArray, $queryID);

            $response['redirect'] .= '?' . http_build_query(array('queryID' => $queryID));
        }
        $this->_helper->json($response);
    }

    public function dropdownAction()
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $params = $request->getParams();
        $searchPhrase = filter_var($params['search'], FILTER_SANITIZE_STRING);
        $pageTypes = Application_Model_Mappers_PageMapper::getInstance()->getPageTypes();
        $filterPageType = array();
        if (!empty($pageTypes) && !empty($params['filterPageType'])) {
            $filterPageTypeConf = explode(',', $params['filterPageType']);
            $filterPageType = array_intersect($pageTypes, $filterPageTypeConf);
        }

        $toasterSearchIndex = Tools_Search_Tools::initIndex();
        $toasterSearchIndex->setResultSetLimit(filter_var($params['limit'], FILTER_SANITIZE_NUMBER_INT));
        $searchTermArray = explode(' ', $searchPhrase);
        $querySearch = new Zend_Search_Lucene_Search_Query_Phrase($searchTermArray);
        $hits = $toasterSearchIndex->find(Zend_Search_Lucene_Search_QueryParser::parse($querySearch, 'utf-8'));
        $searchResults = array_map(
            function ($hit) use ($filterPageType, $searchPhrase) {
                $exclude = false;
                try {
                    $draft = (bool)$hit->draft;
                    $pageType = (int)$hit->pageType;
                } catch (Zend_Search_Lucene_Exception $e) {
                    $draft = false;
                    $pageType = 1;
                }

                if (!empty($filterPageType) && !array_key_exists($pageType, $filterPageType)) {
                    $exclude = true;
                }
                if (!$draft && !$exclude) {
                    return array(
                        'pageId' => $hit->pageId,
                        'url' => $hit->url,
                        'label' => $searchPhrase,
                        'text' => $hit->teaserText,
                        'navName' => $hit->navName,
                        'value' => $hit->h1,
                        'src' => Tools_Page_Tools::getPreview($hit->pageId),
                    );
                }
            },
            $hits
        );
        $searchResults = array_values(array_filter($searchResults));
        $this->_helper->json($searchResults);
    }
}
