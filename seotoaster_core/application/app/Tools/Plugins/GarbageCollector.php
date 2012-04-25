<?php
/**
 * GarbageCollector
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Plugins_GarbageCollector extends Tools_System_GarbageCollector {

	protected function _runOnDefault() {}

	protected function _runOnUpdate() {
		if ($this->_object->getStatus() === Application_Model_Models_Plugin::ENABLED) {
			Tools_Plugins_Tools::registerPluginsIncludePath(true);
		}
		Application_Model_Mappers_EmailTriggersMapper::getInstance()->toggleTriggersStatuses($this->_object->getName(), $this->_object->getStatus());
	}

	protected function _runOnDelete() {
		$this->_removePluginOccurences();
		Application_Model_Mappers_EmailTriggersMapper::getInstance()->unregisterTriggers($this->_object->getName());
		Application_Model_Mappers_EmailTriggersMapper::getInstance()->unregisterRecipients($this->_object->getName());
	}

	protected function _runOnCreate() {
		Tools_Plugins_Tools::registerPluginsIncludePath(true);
		Application_Model_Mappers_EmailTriggersMapper::getInstance()->registerTriggers($this->_object->getName());
		Application_Model_Mappers_EmailTriggersMapper::getInstance()->registerRecipients($this->_object->getName());
	}

	private function _removePluginOccurences() {
		$pattern = '~{\$plugin:' . $this->_object->getName() . '[^{}]*}~usU';

		//removing plugin occurences from content
		$containerMapper = Application_Model_Mappers_ContainerMapper::getInstance();
		$containers      = $containerMapper->fetchAll();
		if(!empty ($containers)) {
			array_walk($containers, function($container, $key, $data) {
				$container->setContent(preg_replace($data['pattern'], '', $container->getContent()));
				$data['mapper']->save($container);
			}, array('pattern' => $pattern, 'mapper' => $containerMapper));
		}

		unset($containers);

		//removing plugin occurences from the templates
		$templateMapper = Application_Model_Mappers_TemplateMapper::getInstance();
		$templates      = $templateMapper->fetchAll();
		if(!empty ($templates)) {
			array_walk($templates, function($template, $key, $data) {
				$template->setContent(preg_replace($data['pattern'], '', $template->getContent()));
				$data['mapper']->save($template);
			}, array('pattern' => $pattern, 'mapper' => $templateMapper));
		}
		unset($templates);
	}
}

