<?php

/**
 * NewsController
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class NewsController extends Zend_Controller_Action {



    public function indexAction() {
		if(in_array($this->getRequest()->getParam('page'), $this->_helper->page->getCanonicMap())) {
			$this->_forward('list');
		}
		else {
			$this->_forward('index', 'index');
		}
    }


    public function listAction() {
		$mapper        = Application_Model_Mappers_TemplateMapper::getInstance();
		$newsTempate   = $mapper->find('news');
		$newsIndexPage = new Application_Model_Models_Page();
		$newsIndexPage->setHeaderTitle($this->_helper->language->translate('Newsroom'));
		$themeData     = Zend_Registry::get('theme');
		$parserOptions = array(
			'websiteUrl'     => $this->_helper->website->getUrl(),
			'websitePath'    => $this->_helper->website->getPath(),
			'currentTheme'   => $this->_helper->config->getConfig('currentTheme'),
			'themePath'      => $themeData['path'],
			'excludeWidgets' => array(

			)
		);
		$parser = new Tools_Content_Parser($newsTempate->getContent(), $newsIndexPage->toArray(), $parserOptions);
		$this->_complete($parser->parse(), $newsIndexPage->toArray(), true);
	}


	private function _complete($pageContent, $pageData, $newsPage = false) {
		$head = '';
		$body = '';
		preg_match('~<head>(.*)</head>~sUui', $pageContent, $head);
		preg_match('~(<body.*>)(.*)</body>~usUi', $pageContent, $body);
		$this->view->head            = $head[1];
		$this->view->websiteUrl      = $this->_helper->website->getUrl();
		$this->view->websiteMainPage = $this->_helper->website->getDefaultPage();
		$this->view->currentTheme    = $this->_helper->config->getConfig('currentTheme');
		$this->view->newsPage        = $newsPage;
		if(Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_ADMINPANEL)) {
			unset($pageData['content']);
			$this->view->pageData = $pageData;
			$body[1] .= $this->_helper->admin->renderAdminPanel($this->_helper->session->getCurrentUser()->getRoleId());
		}
		$this->view->pageData = $pageData;
		$this->view->bodyTag  = $body[1];
		$this->view->content  = $body[2];
	}

    public function viewAction() {

    }
}

