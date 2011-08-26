<?php

/**
 * Page garbage collector
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Page_GarbageCollector extends Tools_System_GarbageCollector {

	protected function _runOnDefault() {

	}

	protected function _runOnDelete() {
		$this->_removePageUrlFromContent();
		$this->_removeRelatedContainers();
	}

	/**
	 * @todo improve/ optimize?
	 */
	private function _removePageUrlFromContent() {
		$websiteHelper       = Zend_Controller_Action_HelperBroker::getStaticHelper('Website');
		$websiteUrl          = $websiteHelper->getUrl();
		unset ($websiteHelper);
		$data                = Application_Model_Mappers_LinkContainerMapper::getInstance()->findByLink($websiteUrl . $this->_object->getUrl());
		if(is_array($data) && !empty ($data)) {
			$containerMapper = Application_Model_Mappers_ContainerMapper::getInstance();
			foreach ($data as $containerData) {
				$container = $containerMapper->find($containerData['id_container']);

				$container->registerObserver(new Tools_Content_GarbageCollector(array(
					'action' => Tools_System_GarbageCollector::CLEAN_ONUPDATE
				)));

				if(!$container instanceof Application_Model_Models_Container) {
					continue;
				}
				$urlPattern = '~<a\s+.*\s*href="' . $containerData['link'] . '"\s*.*\s*>.*</a>~uUs';

				$content = preg_replace($urlPattern, '', $container->getContent());
				$container->setContent($content);
				$containerMapper->save($container);
				$container->notifyObservers();
			}
		}
	}

	private function _removeRelatedContainers() {
		$containerMapper = Application_Model_Mappers_ContainerMapper::getInstance();
		$containers      = $containerMapper->findByPageId($this->_object->getId());
		if(!empty ($containers)) {
			foreach ($containers as $container) {
				$containerMapper->delete($container);
			}
		}
	}
}

