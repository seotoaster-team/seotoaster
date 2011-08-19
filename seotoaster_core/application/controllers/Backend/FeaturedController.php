<?php

/**
 * FeaturedController
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Backend_FeaturedController extends Zend_Controller_Action{

	public function init() {
		parent::init();
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_PAGES)) {
			$this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
		}
		$this->view->websiteUrl = $this->_helper->website->getUrl();
		$this->_helper->AjaxContext()->addActionContext('loadfalist', 'json')->initContext('json');
		$this->_helper->AjaxContext()->addActionContext('addpagetofa', 'json')->initContext('json');
	}

	public function featuredAction() {
		$featuredForm = new Application_Form_Featured();
		if($this->getRequest()->isPost()) {
			if($featuredForm->isValid($this->getRequest()->getParams())) {
				$faMapper     = new Application_Model_Mappers_FeaturedareaMapper();
				$featuredArea = new Application_Model_Models_Featuredarea($featuredForm->getValues());
				$faMapper->save($featuredArea);
				$this->_helper->response->success('Added');
				exit;
			}
		}
		$this->view->pageId = $this->getRequest()->getParam('pid');
		$this->view->faForm = $featuredForm;
	}

	public function loadfalistAction() {
		$faMapper = new Application_Model_Mappers_FeaturedareaMapper();
		$this->view->faeaturedAreas = $faMapper->fetchAll(null, 'name DESC');
		$this->view->faList         = $this->view->render('backend/featured/falist.phtml');
	}

	public function addpagetofaAction() {
		if($this->getRequest()->isPost()) {
			$faMapper   = new Application_Model_Mappers_FeaturedareaMapper();
			$pageMapper = new Application_Model_Mappers_PageMapper();
			$page       = $pageMapper->find($this->getRequest()->getParam('pid'));
			unset($pageMapper);
			$fa       = $faMapper->find($this->getRequest()->getParam('faid'));
			if(!$fa instanceof Application_Model_Models_Featuredarea) {

			}
			$fa->addPage($page);
			$faMapper->save($fa);
		}
	}

}

