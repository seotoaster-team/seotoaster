<?php

class Backend_PluginController extends Zend_Controller_Action {

	public static $_allowedActions = array(
		'fireaction'
	);

	public function init() {
		parent::init();
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_PAGE_PUBLIC)) {
			$this->redirect($this->_helper->website->getUrl(), array('exit' => true));
		}
		if(!Tools_Security_Acl::isActionAllowed(Tools_Security_Acl::RESOURCE_PLUGINS)) {
			$this->redirect($this->_helper->website->getUrl(), array('exit' => true));
		}
		$this->_helper->AjaxContext()->addActionContext('triggerinstall', 'json')->initContext('json');
		$this->_helper->AjaxContext()->addActionContext('trigger', 'json')->initContext('json');
		$this->_helper->AjaxContext()->addActionContext('delete', 'json')->initContext('json');
		$this->_helper->AjaxContext()->addActionContext('list', 'json')->initContext('json');

		$this->view->websiteUrl = $this->_helper->website->getUrl();
	}


	public function pluginAction() {
		$this->view->plugins     = $this->_getPreparedPlugins();
        $this->view->helpSection = 'plugins';
	}

	public function listAction() {
		$this->view->plugins     = $this->_getPreparedPlugins();
		$this->view->pluginsList = $this->view->render('backend/plugin/list.phtml');
	}

    public function readmeAction()
    {
        if ($this->getRequest()->isPost()) {
            $pluginName = $this->getRequest()->getParam('pluginName');
            $miscData = Zend_Registry::get('misc');
            $readmePath = $miscData['pluginsPath'] . $pluginName . '/readme.txt';

            $readmeText = '';
            if (is_readable($readmePath)) {
                $readmeText = $this->_helper->language->translate(nl2br(htmlspecialchars(file_get_contents($readmePath))));
            }

            if (empty($readmeText)) {
                $this->_helper->response->fail($this->_helper->language->translate('Can\'t access readme file'));
            } else {
                $this->_helper->response->success($readmeText);
            }
        }
    }

	private function _getPreparedPlugins() {
		$prepared = array();
		$plugins  = Tools_Plugins_Tools::findAvialablePlugins();
		if(!empty ($plugins)) {
			foreach ($plugins as $pluginName) {
				$plugin      = Tools_Plugins_Tools::findPluginByName($pluginName);
				$preview     = $plugin->getPreview();
				$previewPath = str_replace($this->_helper->website->getUrl(), $this->_helper->website->getPath(), $preview);
				if(!$preview || !file_exists($previewPath)) {
					$plugin->setPreview($this->_helper->website->getUrl() . 'system/images/noimage.png');
				}
				$prepared[] = $plugin;
			}
		}
		return $prepared;
	}

    public function triggerinstallAction() {
        if ($this->getRequest()->isPost()) {
            $pluginMapper = Application_Model_Mappers_PluginMapper::getInstance();
            $plugin       = Tools_Plugins_Tools::findPluginByName($this->getRequest()->getParam('name'));
            $miscData     = Zend_Registry::get('misc');

            if ($plugin->getStatus() == Application_Model_Models_Plugin::DISABLED && $plugin->getId() == NULL) {
                $statusFile = Application_Model_Models_Plugin::INSTALL_FILE_NAME;
                $observerAction = Tools_Plugins_GarbageCollector::CLEAN_ONCREATE;
            } else {
                $statusFile = Application_Model_Models_Plugin::UNINSTALL_FILE_NAME;
                $observerAction = Tools_Plugins_GarbageCollector::CLEAN_ONDELETE;
            }

            $sqlFilePath  = $this->_helper->website->getPath().$miscData['pluginsPath'].$plugin->getName().'/system/'.$statusFile;
            if (file_exists($sqlFilePath)) {
                try {
                    $sqlFileContent = Tools_Filesystem_Tools::getFile($sqlFilePath);
                    if (strlen($sqlFileContent)) {
                        $queries = Tools_System_SqlSplitter::split($sqlFileContent);
                        if (is_array($queries) && !empty ($queries)) {
                            $dbAdapter = Zend_Registry::get('dbAdapter');
                            try {
                                array_walk($queries, function($query) use ($dbAdapter) {
                                    if(strlen(trim($query))) {
                                        $dbAdapter->query($query);
                                    }
                                });
                            }
                            catch (Exception $e) {
                                error_log($e->getMessage());
                                $this->_helper->response->fail($e->getMessage());
                            }
                        }
                    }
                }
                catch (Exceptions_SeotoasterPluginException $se) {
                    error_log($se->getMessage());
                    $this->_helper->response->fail($se->getMessage());
                }
            }

            $plugin->registerObserver(
                new Tools_Plugins_GarbageCollector(
                    array('action' => $observerAction)
                )
            );

            if ($plugin->getStatus() == Application_Model_Models_Plugin::DISABLED && $plugin->getId() == NULL) {
                $plugin->setStatus(Application_Model_Models_Plugin::ENABLED);
                $pluginMapper->save($plugin);
                $this->view->buttonText  = 'Uninstall';
                $this->view->endisButton = true;
            }
            elseif ($plugin->getStatus() == Application_Model_Models_Plugin::ENABLED || $plugin->getStatus() == Application_Model_Models_Plugin::DISABLED && $plugin->getId() != NULL) {
                $pluginMapper->delete($plugin);
                $this->view->buttonText = 'Install';
                $this->view->endisButton = false;
            }

            $this->_helper->cache->clean(null, null, array('plugins'));
            $this->_helper->cache->clean('admin_addmenu', $this->_helper->session->getCurrentUser()->getRoleId());
        }
    }

    public function triggerAction()
    {
        if ($this->getRequest()->isPost()) {
            $plugin = Tools_Plugins_Tools::findPluginByName($this->getRequest()->getParam('name'));
            $plugin->registerObserver(
                new Tools_Plugins_GarbageCollector(array('action' => Tools_System_GarbageCollector::CLEAN_ONUPDATE))
            );
            if ($plugin->getStatus() == Application_Model_Models_Plugin::ENABLED) {
                $plugin->setStatus(Application_Model_Models_Plugin::DISABLED);
                $buttonText = 'Enable';
            } else {
                $plugin->setStatus(Application_Model_Models_Plugin::ENABLED);
                $buttonText = 'Disable';
            }
            $this->view->responseText = Application_Model_Mappers_PluginMapper::getInstance()->save($plugin);
            $this->view->buttonText = $buttonText;
            $this->_helper->cache->clean('admin_addmenu', $this->_helper->session->getCurrentUser()->getRoleId());
        }
    }

	public function deleteAction() {
		if($this->getRequest()->isPost()) {
			$plugin       = Tools_Plugins_Tools::findPluginByName($this->getRequest()->getParam('id'));
			$plugin->registerObserver(new Tools_Plugins_GarbageCollector(array(
					'action' => Tools_System_GarbageCollector::CLEAN_ONDELETE
			)));
            $miscData     = Zend_Registry::get('misc');
            $sqlFilePath = $this->_helper->website->getPath() . $miscData['pluginsPath'] . $plugin->getName() . '/system/' .
						   (Application_Model_Models_Plugin::UNINSTALL_FILE_NAME);
            if(file_exists($sqlFilePath)) {
				$sqlFileContent = Tools_Filesystem_Tools::getFile($sqlFilePath);
				if(strlen($sqlFileContent)) {
                    $queries = Tools_System_SqlSplitter::split($sqlFileContent);
                }
            }
            $delete = Tools_Filesystem_Tools::deleteDir($this->_helper->website->getPath() . 'plugins/' . $plugin->getName());
			if(!$delete) {
				$this->_helper->response->fail('Can\'t remove plugin\'s directory (not enough permissions). Plugin was uninstalled.');
				exit;
			}
            if(is_array($queries) && !empty ($queries)) {
                $dbAdapter = Zend_Registry::get('dbAdapter');
				try {
					array_walk($queries, function($query, $key, $adapter) {
                        if(strlen(trim($query))) {
                            $adapter->query($query);
                        }
                    }, $dbAdapter);
                    Application_Model_Mappers_PluginMapper::getInstance()->delete($plugin);
				}
				catch (Exception $e) {
                    error_log($e->getMessage());
					$this->_helper->response->fail($e->getMessage());
				}
			}
            
			$this->_helper->cache->clean(null, null, array('plugins'));
			$this->_helper->cache->clean('admin_addmenu', $this->_helper->session->getCurrentUser()->getRoleId());
			$this->_helper->response->success('Removed');
		}
	}

	public function fireactionAction() {
		$this->_helper->viewRenderer->setNoRender(true);
		$pluginName = $this->getRequest()->getParam('name');

        //we will fire the action in the case when plugin is enabled
        $toasterPlugin = Application_Model_Mappers_PluginMapper::getInstance()->findByName($pluginName);
        if(($toasterPlugin instanceof Application_Model_Models_Plugin) && ($toasterPlugin->getStatus() == Application_Model_Models_Plugin::ENABLED)) {
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
}
