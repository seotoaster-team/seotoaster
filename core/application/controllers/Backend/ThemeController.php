<?php

class Backend_ThemeController extends Zend_Controller_Action {

	public function  init() {
		parent::init();
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_THEMES)) {
			$this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
		}
		$this->view->websiteUrl = $this->_helper->website->getUrl();
		$this->_helper->layout->disableLayout();
	}

	public function templateAction() {
		$templateForm = new Application_Form_Template();
		$templateId   = $this->getRequest()->getParam('id');
		$mapper = new Application_Model_Mappers_TemplateMapper();
		if(!$this->getRequest()->isPost()) {
			if($templateId) {
				$template = $mapper->find($templateId);
				if($template instanceof Application_Model_Models_Template) {
					$templateForm->getElement('content')->setValue($template->getContent());
					$templateForm->getElement('name')->setValue($template->getName());
					$templateForm->getElement('id')->setValue($template->getId());
					$templateForm->getElement('previewImage')->setValue($template->getPreviewImage());
					$templateForm->getElement('themeName')->setValue($template->getThemeName());
				}
			}
		}
		else {
			if($templateForm->isValid($this->getRequest()->getParams())) {
				$templateData = $templateForm->getValues();
				$template = new Application_Model_Models_Template($templateData);
				$this->getResponse()->setHttpResponseCode(200);
				$this->getResponse()->setBody($mapper->save($template));
				$this->getResponse()->sendResponse();
				exit;
			}
		}
		$this->view->templateForm = $templateForm;		
	}
}

