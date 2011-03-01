<?php
/**
 * Description of PageController
 *
 * @author iamne
 */
class Backend_PageController extends Zend_Controller_Action {

	public function init() {
		parent::init();
		$this->_helper->viewRenderer->setNoRender(true);
	}

	public function indexAction() {
		 Zend_Debug::dump($this->getRequest()->getParams(), 'Page Controller'); die();
	}

	public function addAction() {
		if($this->getRequest()->isPost()) {
			
		}
		
	}

}

