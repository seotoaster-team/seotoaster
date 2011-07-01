<?php

/**
 * SeoController
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Backend_SeoController extends Zend_Controller_Action {

	public function init() {
		parent::init();
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_PAGES)) {
			$this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
		}
		$this->view->websiteUrl = $this->_helper->website->getUrl();
	}

	public function robotsAction() {
		$robotsForm = new Application_Form_Robots();
		if(!$this->getRequest()->isPost()) {
			$robotstxtContent = Tools_Filesystem_Tools::getFile('robots.txt');
			$robotsForm->setContent($robotstxtContent);
		}
		else {
			if($robotsForm->isValid($this->getRequest()->getParams())) {
				$robotsData = $robotsForm->getValues();
				try{
					Tools_Filesystem_Tools::saveFile('robots.txt', $robotsData['content']);
					$this->_helper->response->success('Robots.txt updated.');
				}
				catch (Exception $e) {
					$this->_helper->response->fail($e->getMessage());
				}
			}
		}
		$this->view->form = $robotsForm;
	}

	public function redirectsAction() {
		
	}

}

