<?php

class Backend_PluginController extends Zend_Controller_Action {

	public function init() {
		parent::init();
		$this->_helper->viewRenderer->setNoRender(true);
	}

	public function fireactionAction() {
		$pluginName = $this->getRequest()->getParam('name');
		$pageData   = array('websiteUrl' => $this->_helper->website->getUrl());
		try {
			$plugin = Tools_Factory_PluginFactory::createPlugin($pluginName, array(), $pageData);
			$plugin->run($this->getRequest()->getParams());
		}
		catch (Exception $e) {
			die($e->getMessage());
		}
	}

}

