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
		$this->_helper->AjaxContext()->addActionContext('rempagefromfa', 'json')->initContext('json');
	}

	public function featuredAction() {
		$featuredForm = new Application_Form_Featured();
		if($this->getRequest()->isPost()) {
			if($featuredForm->isValid($this->getRequest()->getParams())) {
				$featuredArea = new Application_Model_Models_Featuredarea($featuredForm->getValues());
				Application_Model_Mappers_FeaturedareaMapper::getInstance()->save($featuredArea);
				$this->_helper->response->success('Added');
				exit;
			}
		}
		$pageId                    = $this->getRequest()->getParam('pid');
		$this->view->pageId        = $pageId;
		$this->view->faForm        = $featuredForm;
	}

	public function loadfalistAction() {
		$render        = $this->getRequest()->getParam('render', true);
		$namesOnly     = $this->getRequest()->getParam('namesonly', false);
		$featuredAreas = Application_Model_Mappers_FeaturedareaMapper::getInstance()->fetchAll(null, 'name DESC');

		if($namesOnly) {
			$names = array();
			foreach ($featuredAreas as $area) {
				 $names[] = array(
					 'name' => $area->getName(),
					 'id'   => $area->getId()
				 );
			}
			$this->view->responseData = $names;
		}

		$this->view->faeaturedAreas = $featuredAreas;
		$pageId                     = $this->getRequest()->getParam('pid');
		if($pageId) {
			$ids = array();
			$currentFareas = Application_Model_Mappers_FeaturedareaMapper::getInstance()->findAreasByPageId($pageId);
			if(!empty ($currentFareas)) {
				foreach ($currentFareas as $currFa) {
					$ids[] = $currFa->getId();
				}
			}
		}
		$this->view->currentFareasIds  = (!empty($ids)) ? $ids : array();

		if($render) {
			$this->view->faList = $this->view->render('backend/featured/falist.phtml');
		}
	}

	public function addpagetofaAction() {
		if($this->getRequest()->isPost()) {
			$page     = Application_Model_Mappers_PageMapper::getInstance()->find($this->getRequest()->getParam('pid'));
			$fa       = Application_Model_Mappers_FeaturedareaMapper::getInstance()->find($this->getRequest()->getParam('faid'));
			if(!$fa instanceof Application_Model_Models_Featuredarea) {

			}
			if(!$page instanceof Application_Model_Models_Page) {

			}
			$fa->addPage($page);
			Application_Model_Mappers_FeaturedareaMapper::getInstance()->save($fa);
		}
	}

	public function rempagefromfaAction() {
		if($this->getRequest()->isPost()) {
			$page     = Application_Model_Mappers_PageMapper::getInstance()->find($this->getRequest()->getParam('pid'));
			$fa       = Application_Model_Mappers_FeaturedareaMapper::getInstance()->find($this->getRequest()->getParam('faid'));
			if(!$fa instanceof Application_Model_Models_Featuredarea) {

			}
			$fa->deletePage($page);
			Application_Model_Mappers_FeaturedareaMapper::getInstance()->save($fa);
		}
	}

}

