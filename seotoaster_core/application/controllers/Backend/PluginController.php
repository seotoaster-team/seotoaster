<?php

class Backend_PluginController extends Zend_Controller_Action {



	public function init() {
		parent::init();
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_PAGE_PUBLIC)) {
			$this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
		}
		$this->_helper->AjaxContext()->addActionContext('triggerinstall', 'json')->initContext('json');
		$this->_helper->AjaxContext()->addActionContext('trigger', 'json')->initContext('json');
		$this->_helper->AjaxContext()->addActionContext('delete', 'json')->initContext('json');
		$this->_helper->AjaxContext()->addActionContext('list', 'json')->initContext('json');

		$this->view->websiteUrl = $this->_helper->website->getUrl();
	}


	public function pluginAction() {
		$this->view->plugins = $this->_getPreparedPlugins();
	}

	public function listAction() {
		$this->view->plugins     = $this->_getPreparedPlugins();
		$this->view->pluginsList = $this->view->render('backend/plugin/list.phtml');
	}

	private function _getPreparedPlugins() {
		$prepared = array();
		$plugins  = Tools_Plugins_Tools::findAvialablePlugins();
		if(!empty ($plugins)) {
			foreach ($plugins as $pluginName) {
				$prepared[] = Tools_Plugins_Tools::findPluginByName($pluginName);
			}
		}
		return $prepared;
	}

	public function triggerinstallAction() {
		if($this->getRequest()->isPost()) {
			$pluginMapper = new Application_Model_Mappers_PluginMapper();
			$plugin       = Tools_Plugins_Tools::findPluginByName($this->getRequest()->getParam('name'));
			$miscData     = Zend_Registry::get('misc');

			$sqlFilePath = $this->_helper->website->getPath() . $miscData['pluginsPath'] . $plugin->getName() . '/system/' .
						   (($plugin->getStatus() == Application_Model_Models_Plugin::DISABLED) ? Application_Model_Models_Plugin::INSTALL_FILE_NAME : Application_Model_Models_Plugin::UNINSTALL_FILE_NAME);

			if(file_exists($sqlFilePath)) {
				try {
					$sqlFileContent = Tools_Filesystem_Tools::getFile($sqlFilePath);
					if(strlen($sqlFileContent)) {
						$queries = explode(';', $sqlFileContent);
						if(is_array($queries) && !empty ($queries)) {
							$dbAdapter = Zend_Registry::get('dbAdapter');
							try {
								array_walk($queries, function($query, $key, $adapter) {
									if(strlen(trim($query))) {
										$adapter->query($query);
									}
								}, $dbAdapter);
							}
							catch (Exception $e) {
								$this->_helper->response->fail($e->getMessage());
							}
						}
					}
				}
				catch(Exceptions_SeotoasterPluginException $se) {
					$this->_helper->response->fail($se->getMessage());
				}
			}

			if($plugin->getStatus() == Application_Model_Models_Plugin::DISABLED) {
				$plugin->setStatus(Application_Model_Models_Plugin::ENABLED);
				$pluginMapper->save($plugin);
				$this->view->buttonText  = 'Uninstall';
				$this->view->endisButton = true;
			}

			elseif($plugin->getStatus() == Application_Model_Models_Plugin::ENABLED) {

				$pluginMapper->delete($plugin);

				$this->view->buttonText = 'Install';
				$this->view->endisButton = false;
			}
		}
	}

	public function triggerAction() {
		if($this->getRequest()->isPost()) {
			$pluginMapper             = new Application_Model_Mappers_PluginMapper();
			$plugin                   = Tools_Plugins_Tools::findPluginByName($this->getRequest()->getParam('name'));
			$this->view->responseText = $pluginMapper->save($plugin->setStatus(($plugin->getStatus() == Application_Model_Models_Plugin::ENABLED) ? Application_Model_Models_Plugin::DISABLED : Application_Model_Models_Plugin::ENABLED));
			$this->view->buttonText   = ($plugin->getStatus() == Application_Model_Models_Plugin::ENABLED) ? 'Disable' : 'Enable';
		}
	}

	public function deleteAction() {
		if($this->getRequest()->isPost()) {
			$pluginMapper = new Application_Model_Mappers_PluginMapper();
			$plugin       = Tools_Plugins_Tools::findPluginByName($this->getRequest()->getParam('id'));
			$delete       = Tools_Filesystem_Tools::deleteDir($this->_helper->website->getPath() . 'plugins/' . $plugin->getName());
			$plugin->registerObserver(new Tools_Plugins_GarbageCollector(array(
					'action' => Tools_System_GarbageCollector::CLEAN_ONDELETE
			)));
			$pluginMapper->delete($plugin);
			if(!$delete) {
				$this->_helper->response->fail('Can\'t remove plugin\'s directory (not enough permissions). Plugin was uninstalled.');
				exit;
			}
			$this->_helper->response->success('Removed');
		}
	}

	public function fireactionAction() {
		$this->_helper->viewRenderer->setNoRender(true);
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