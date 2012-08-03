<?php
/**
 * ApiController.php
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Backend_ApiController extends Zend_Controller_Action {

	public function init(){
		parent::init();
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
	}

	public function indexAction(){
		$plugin = filter_var($this->_request->getParam('plugin'), FILTER_SANITIZE_STRING);
		$service = filter_var($this->_request->getParam('service'), FILTER_SANITIZE_STRING);

		if (empty($plugin) && empty($service)){
			$this->_response->clearAllHeaders()->clearBody();
			$this->_response->setHttpResponseCode(403)->sendResponse();
		}

		$service = Tools_Factory_RestServiceFactory::createService($plugin, $service);
		if ($service instanceof Api_Service_Abstract) {
			return $service->dispatch();
		}

	}

}
