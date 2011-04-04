<?php
/**
 * Description of PageController
 *
 * @author iamne
 */
class Backend_PageController extends Zend_Controller_Action {

	public function init() {
		parent::init();
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_PAGES)) {
			$this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
		}
		$this->view->websiteUrl = $this->_helper->website->getUrl();
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
	}

	public function pageAction() {
		$pageForm = new Application_Form_Page();
		if($this->getRequest()->isPost()) {
			
		}
		else {
			echo $pageForm;
			//Zend_Debug::dump('paaaaage');
		}
	}

}

